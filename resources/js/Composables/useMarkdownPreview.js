import { ref, watch } from 'vue';
import { marked } from 'marked';
import DOMPurify from 'dompurify';

marked.setOptions({
    breaks: true,
    gfm: true,
});

const ALLOWED_TAGS = [
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    'p', 'br', 'hr',
    'ul', 'ol', 'li',
    'strong', 'em', 'code', 'pre', 'blockquote',
    'a', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
    'del', 'sup', 'sub', 'span',
];

const ALLOWED_ATTR = ['href', 'title'];

export function useMarkdownPreview(source) {
    const renderedHtml = ref('');
    const parseWarnings = ref([]);

    const render = (markdown) => {
        if (!markdown) {
            renderedHtml.value = '';
            parseWarnings.value = [];
            return;
        }

        const warnings = [];

        if (!markdown.match(/^#{1,6}\s+/m)) {
            warnings.push('No markdown headings found. Consider using # sections for better organization.');
        }

        const backtickCount = (markdown.match(/```/g) || []).length;
        if (backtickCount % 2 !== 0) {
            warnings.push('Unclosed code block detected (unmatched ``` markers).');
        }

        try {
            const rawHtml = marked.parse(markdown);
            renderedHtml.value = DOMPurify.sanitize(rawHtml, {
                ALLOWED_TAGS,
                ALLOWED_ATTR,
            });
            parseWarnings.value = warnings;
        } catch (e) {
            parseWarnings.value = [...warnings, 'Failed to parse markdown: ' + e.message];
            renderedHtml.value = '';
        }
    };

    watch(source, render, { immediate: true });

    return { renderedHtml, parseWarnings };
}
