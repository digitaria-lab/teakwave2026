</div>
<script>
window.UPLOAD_SETTINGS = <?php echo json_encode(upload_settings_for_js()); ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php if (in_array(basename($_SERVER['PHP_SELF']), ['products.php','contents.php','videos.php'])): ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>
<?php endif; ?>
<?php if (in_array(basename($_SERVER['PHP_SELF']), ['product-add.php','product-edit.php','content-add.php','content-edit.php'])): ?>
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function closeAllFullscreenEditors() {
        document.querySelectorAll('.quill-wrapper.quill-fullscreen').forEach(function (wrapper) {
            wrapper.classList.remove('quill-fullscreen');

            const btn = wrapper.querySelector('.quill-fullscreen-btn');
            if (btn) btn.innerHTML = '<i class="bi bi-arrows-fullscreen"></i> Fullscreen';
        });

        document.body.classList.remove('quill-fullscreen-open');
    }

    function beautifyHtml(html) {
        if (!html) return '';

        let formatted = '';
        let indent = 0;
        const tab = '    ';

        html
            .replace(/>\s+</g, '><')
            .replace(/</g, '\n<')
            .split('\n')
            .filter(Boolean)
            .forEach(function (node) {
                if (/^<\/\w/.test(node)) {
                    indent = Math.max(indent - 1, 0);
                }

                formatted += tab.repeat(indent) + node.trim() + '\n';

                if (
                    /^<\w[^>]*[^/]>$/.test(node) &&
                    !/^<(br|hr|img|input|meta|link|source|area|base|col|embed|param|track|wbr)\b/i.test(node)
                ) {
                    indent += 1;
                }
            });

        return formatted.trim();
    }

    function encodeBase64Utf8(value) {
        try {
            return btoa(unescape(encodeURIComponent(value || '')));
        } catch (error) {
            console.error('Gagal encode HTML editor:', error);
            return '';
        }
    }

    function findEncodedHtmlField(form, fieldName) {
        if (!form || !fieldName) return null;

        return Array.prototype.slice.call(form.querySelectorAll('input[type="hidden"]')).find(function (input) {
            return input.name === fieldName + '_encoded';
        }) || null;
    }

    document.querySelectorAll('textarea.wysiwyg').forEach(function (textarea, index) {
        const wrapper = document.createElement('div');
        wrapper.className = 'quill-wrapper';

        const editorTop = document.createElement('div');
        editorTop.className = 'quill-editor-top';

        const label = document.createElement('span');
        label.className = 'quill-editor-label';
        label.textContent = 'Visual Editor';

        const actions = document.createElement('div');
        actions.className = 'quill-editor-actions';

        const htmlBtn = document.createElement('button');
        htmlBtn.type = 'button';
        htmlBtn.className = 'btn btn-sm btn-outline-secondary quill-html-toggle-btn';
        htmlBtn.innerHTML = '<i class="bi bi-code-slash"></i> View/Edit HTML';

        const formatBtn = document.createElement('button');
        formatBtn.type = 'button';
        formatBtn.className = 'btn btn-sm btn-outline-secondary quill-format-html-btn d-none';
        formatBtn.innerHTML = '<i class="bi bi-braces"></i> Format HTML';

        const fullscreenBtn = document.createElement('button');
        fullscreenBtn.type = 'button';
        fullscreenBtn.className = 'btn btn-sm btn-outline-primary quill-fullscreen-btn';
        fullscreenBtn.innerHTML = '<i class="bi bi-arrows-fullscreen"></i> Fullscreen';

        actions.appendChild(htmlBtn);
        actions.appendChild(formatBtn);
        actions.appendChild(fullscreenBtn);

        editorTop.appendChild(label);
        editorTop.appendChild(actions);

        const editor = document.createElement('div');
        editor.className = 'quill-editor';
        editor.id = 'quill-editor-' + index;
        editor.innerHTML = textarea.value || '';

        const sourceTextarea = textarea;
        sourceTextarea.classList.add('quill-html-source');
        sourceTextarea.setAttribute('spellcheck', 'false');
        sourceTextarea.setAttribute('data-html-source', 'true');

        sourceTextarea.parentNode.insertBefore(wrapper, sourceTextarea.nextSibling);
        wrapper.appendChild(editorTop);
        wrapper.appendChild(editor);
        wrapper.appendChild(sourceTextarea);

        sourceTextarea.style.display = 'none';

        const quill = new Quill(editor, {
            theme: 'snow',
            placeholder: 'Tulis konten di sini...',
            modules: {
                toolbar: [
                    [{ header: [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ align: [] }],
                    ['link', 'blockquote', 'code-block'],
                    ['clean']
                ]
            }
        });

        function syncVisualToHtml() {
            sourceTextarea.value = quill.root.innerHTML;
        }

        function syncHtmlToVisual() {
            quill.root.innerHTML = sourceTextarea.value || '';
        }

        function setHtmlMode(isHtmlMode) {
            if (isHtmlMode) {
                syncVisualToHtml();

                wrapper.classList.add('html-mode');
                label.textContent = 'HTML Source';
                sourceTextarea.style.display = 'block';
                editor.style.display = 'none';

                htmlBtn.innerHTML = '<i class="bi bi-eye"></i> Back to Visual';
                htmlBtn.classList.remove('btn-outline-secondary');
                htmlBtn.classList.add('btn-primary');

                formatBtn.classList.remove('d-none');
                sourceTextarea.focus();
            } else {
                syncHtmlToVisual();

                wrapper.classList.remove('html-mode');
                label.textContent = 'Visual Editor';
                sourceTextarea.style.display = 'none';
                editor.style.display = '';

                htmlBtn.innerHTML = '<i class="bi bi-code-slash"></i> View/Edit HTML';
                htmlBtn.classList.remove('btn-primary');
                htmlBtn.classList.add('btn-outline-secondary');

                formatBtn.classList.add('d-none');
                quill.focus();
            }
        }

        htmlBtn.addEventListener('click', function () {
            setHtmlMode(!wrapper.classList.contains('html-mode'));
        });

        formatBtn.addEventListener('click', function () {
            sourceTextarea.value = beautifyHtml(sourceTextarea.value);
            sourceTextarea.focus();
        });

        fullscreenBtn.addEventListener('click', function () {
            const isFullscreen = wrapper.classList.contains('quill-fullscreen');

            closeAllFullscreenEditors();

            if (!isFullscreen) {
                wrapper.classList.add('quill-fullscreen');
                document.body.classList.add('quill-fullscreen-open');
                fullscreenBtn.innerHTML = '<i class="bi bi-fullscreen-exit"></i> Exit Fullscreen';

                setTimeout(function () {
                    if (wrapper.classList.contains('html-mode')) {
                        sourceTextarea.focus();
                    } else {
                        quill.focus();
                    }
                }, 120);
            }
        });

        const form = sourceTextarea.closest('form');

        if (form) {
            form.addEventListener('submit', function () {
                if (wrapper.classList.contains('html-mode')) {
                    syncHtmlToVisual();
                }

                sourceTextarea.value = quill.root.innerHTML;

                const originalName = sourceTextarea.dataset.originalName || sourceTextarea.getAttribute('name') || '';
                const encodedField = findEncodedHtmlField(form, originalName);

                if (encodedField) {
                    encodedField.value = encodeBase64Utf8(sourceTextarea.value || '');
                    sourceTextarea.dataset.originalName = originalName;
                    sourceTextarea.value = '';
                    sourceTextarea.setAttribute('name', originalName + '_raw_ignored');
                }
            });
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAllFullscreenEditors();
        }
    });
});
</script>
<?php endif; ?>
<?php $adminJsVersion = @filemtime(__DIR__ . '/../assets/js/admin.js') ?: '1'; ?>
<script src="assets/js/admin.js?v=<?php echo rawurlencode((string) $adminJsVersion); ?>"></script>
</body>
</html>
