const { test, expect } = require('@playwright/test');

async function expectNoHorizontalOverflow(page) {
    const overflow = await page.evaluate(() => ({
        viewport: document.documentElement.clientWidth,
        content: document.documentElement.scrollWidth
    }));
    expect(overflow.content).toBeLessThanOrEqual(overflow.viewport + 1);
}

async function expectElementNotCovered(locator) {
    const visibleAtCenter = await locator.evaluate((element) => {
        const rect = element.getBoundingClientRect();
        const x = Math.min(window.innerWidth - 1, Math.max(0, rect.left + rect.width / 2));
        const y = Math.min(window.innerHeight - 1, Math.max(0, rect.top + rect.height / 2));
        const hit = document.elementFromPoint(x, y);
        return hit !== null && (hit === element || element.contains(hit));
    });
    expect(visibleAtCenter).toBe(true);
}

test('public home renders without horizontal overflow', async ({ page }) => {
    const response = await page.goto('/');
    expect(response).not.toBeNull();
    expect(response.status()).toBe(200);
    await expect(page.locator('main#main-content')).toBeVisible();

    await expectNoHorizontalOverflow(page);
});

test('critical homepage sections keep their order, size and stacking', async ({ page }) => {
    await page.goto('/');

    const hero = page.locator('.cms-block--hero');
    const counters = page.locator('.cms-block--counters');
    const projects = page.locator('.cms-block--image_cards');
    const media = page.locator('.cms-block--media_gallery');
    await expect(hero).toBeVisible();
    await expect(counters).toBeVisible();
    await expect(projects).toBeVisible();
    await expect(media).toBeVisible();

    const sectionGeometry = await page.evaluate(() => {
        const selectors = [
            '.cms-block--hero',
            '.cms-block--counters',
            '.cms-block--image_cards',
            '.cms-block--media_gallery'
        ];
        return selectors.map((selector) => {
            const rect = document.querySelector(selector).getBoundingClientRect();
            return { top: rect.top + window.scrollY, bottom: rect.bottom + window.scrollY, width: rect.width };
        });
    });
    for (let i = 1; i < sectionGeometry.length; i += 1) {
        expect(sectionGeometry[i].top).toBeGreaterThan(sectionGeometry[i - 1].top);
    }
    for (const section of sectionGeometry) {
        expect(section.bottom).toBeGreaterThan(section.top);
        expect(section.width).toBeLessThanOrEqual(await page.evaluate(() => document.documentElement.clientWidth + 1));
    }

    const firstCounter = page.locator('.counter').first();
    await firstCounter.scrollIntoViewIfNeeded();
    await expectElementNotCovered(firstCounter);

    const firstMediaCard = page.locator('.mediacard:visible').first();
    await firstMediaCard.scrollIntoViewIfNeeded();
    await expectElementNotCovered(firstMediaCard);
    const cardBox = await firstMediaCard.boundingBox();
    expect(cardBox).not.toBeNull();
    expect(cardBox.width).toBeGreaterThan(160);
    expect(cardBox.height).toBeLessThan(cardBox.width * 1.6);

    await expectNoHorizontalOverflow(page);
});

test('media tabs filter cards without breaking the gallery', async ({ page }) => {
    await page.goto('/');
    const gallery = page.locator('[data-media-gallery]');
    await expect(gallery).toBeVisible();

    const photoTab = gallery.locator('[data-media-tab="photo"]');
    const videoTab = gallery.locator('[data-media-tab="video"]');
    await expect(videoTab).toHaveAttribute('aria-pressed', 'true');
    await expect(gallery.locator('[data-media-kind="video"]').first()).toBeVisible();
    await expect(gallery.locator('[data-media-kind="photo"]').first()).toBeHidden();

    await photoTab.click();
    await expect(photoTab).toHaveAttribute('aria-pressed', 'true');
    await expect(videoTab).toHaveAttribute('aria-pressed', 'false');
    await expect(gallery.locator('[data-media-kind="photo"]').first()).toBeVisible();
    await expect(gallery.locator('[data-media-kind="video"]').first()).toBeHidden();
    await expectNoHorizontalOverflow(page);
});

test('project cards retain smooth nested transitions', async ({ page, isMobile }) => {
    test.skip(isMobile, 'Hover motion is checked with a fine pointer');
    await page.goto('/');

    const card = page.locator('.cms-block--image_cards .imgcard').first();
    const image = card.locator('.imgcard__media');
    await card.scrollIntoViewIfNeeded();
    await expect(card).toBeVisible();

    const motion = await image.evaluate((element) => {
        const style = getComputedStyle(element);
        return {
            property: style.transitionProperty,
            duration: style.transitionDuration,
            before: style.transform
        };
    });
    expect(motion.property).toContain('transform');
    expect(motion.duration).not.toMatch(/^(0s)(, 0s)*$/);

    await card.hover();
    await expect.poll(() => image.evaluate((element) => getComputedStyle(element).transform)).not.toBe(motion.before);
});

test('Uzbek home uses localized title and secure language URL', async ({ page }) => {
    const response = await page.goto('/uz');
    expect(response).not.toBeNull();
    expect(response.status()).toBe(200);
    await expect(page).toHaveTitle(/Bosh sahifa/);
    expect(page.url()).toMatch(/^http:\/\/127\.0\.0\.1:8080\/uz\/?$/);
});

test('mobile menu opens and closes accessibly', async ({ page, isMobile }) => {
    test.skip(!isMobile, 'Mobile-only interaction');
    await page.goto('/');
    const burger = page.locator('[data-mobile-menu-toggle]').first();
    await expect(burger).toBeVisible();
    await burger.click();
    await expect(page.locator('body')).toHaveClass(/mobile-menu-open/);
    await expect(burger).toHaveAttribute('aria-expanded', 'true');
    await page.keyboard.press('Escape');
    await expect(page.locator('body')).not.toHaveClass(/mobile-menu-open/);
    await expect(burger).toHaveAttribute('aria-expanded', 'false');
});

test('health endpoint and admin login are reachable', async ({ page, request }) => {
    const health = await request.get('/health');
    expect(health.status()).toBe(200);
    const healthPayload = await health.json();
    expect(healthPayload.status).toMatch(/^(ok|degraded)$/);

    const response = await page.goto('/admin/login');
    expect(response).not.toBeNull();
    expect(response.status()).toBe(200);
    await expect(page.locator('input[name="username"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
});
