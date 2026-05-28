import MarkdownParser from 'markdown-it';
import MarkdownMentionPlugin from '@/kernel/plugins/markdownit/mention.plugin.js';
import MarkdownHashtagPlugin from '@/kernel/plugins/markdownit/hashtag.plugin.js';

const mdInlineRenderer = (text = '', options = {}) => {
    const parser = new MarkdownParser({
		html: true,
		breaks: true,
		linkify: true,
		...options
	});

    parser.use(MarkdownMentionPlugin);
    parser.use(MarkdownHashtagPlugin);

	return parser.renderInline(text);
}

export { mdInlineRenderer };
