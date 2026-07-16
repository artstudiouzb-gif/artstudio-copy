(function () {
    'use strict';

    var loading = false;
    var callbacks = [];

    function loadTinyMCE(callback) {
        if (window.tinymce) {
            callback();
            return;
        }
        callbacks.push(callback);
        if (loading) { return; }
        loading = true;

        // Локальный самохостинг TinyMCE (без внешнего CDN): движок и все
        // ресурсы (скины/плагины/язык) лежат в vendor/tinymce — работает на
        // голом хостинге и не шлёт запросы админов на сторонние домены.
        var script = document.createElement('script');
        script.src = '/assets/js/vendor/tinymce/tinymce.min.js';
        script.onload = function () {
            while (callbacks.length > 0) {
                var cb = callbacks.shift();
                try { cb(); } catch (e) { console.error(e); }
            }
        };
        script.onerror = function () {
            console.error('Failed to load TinyMCE');
        };
        document.head.appendChild(script);
    }

    function attach(textarea) {
        if (textarea.dataset.wysiwygReady === '1') { return; }
        textarea.dataset.wysiwygReady = '1';

        loadTinyMCE(function () {
            // Generate a unique ID if the textarea doesn't have one
            if (!textarea.id) {
                textarea.id = 'wysiwyg-' + Math.random().toString(36).substr(2, 9);
            }

            window.tinymce.init({
                selector: '#' + textarea.id,
                // base_url обязателен при самохостинге: указывает, откуда
                // грузить скины, темы, модели и плагины.
                base_url: '/assets/js/vendor/tinymce',
                suffix: '.min',
                license_key: 'gpl',
                language: 'ru',
                language_url: '/assets/js/vendor/tinymce/langs/ru.js',
                height: 400,
                menubar: false,
                branding: false,
                promotion: false,
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'wordcount'
                ],
                toolbar: 'undo redo | blocks | ' +
                    'bold italic underline strikethrough | subscript superscript | blockquote | alignleft aligncenter ' +
                    'alignright alignjustify | bullist numlist outdent indent | ' +
                    'link image table | removeformat code fullscreen',
                // Стандартный вид редактора. Дефолтный content.css TinyMCE 6
                // зажимает текст узкой колонкой (body { max-width; margin auto })
                // — снимаем, чтобы контент занимал всю ширину окна.
                content_style: 'body { max-width: none; margin: 1rem; }',
                setup: function (editor) {
                    editor.on('change input blur', function () {
                        editor.save(); // Synchronizes TinyMCE content back to the textarea
                        textarea.dispatchEvent(new Event('input')); // Triggers change event for AJAX-forms
                    });
                },
                // Integrate with the system's MediaPicker (file manager)
                file_picker_callback: function (callback, value, meta) {
                    if (meta.filetype === 'image' && window.MediaPicker) {
                        window.MediaPicker.pick(function (url) {
                            callback(url, { alt: '' });
                        });
                    }
                }
            });
        });
    }

    window.ArtEditor = { attach: attach };
})();
