export default function (md) {
    md.inline.ruler.push('hashtag', (state, silent) => {
        const hashtagPattern = /^#([A-Za-z0-9_]{2,64})/;
        const pos = state.pos;
        const src = state.src;

        if (src.charAt(pos) !== '#') {
            return false;
        }

        if (pos > 0 && /[A-Za-z0-9_]/.test(src.charAt(pos - 1))) {
            return false;
        }

        const match = src.slice(pos).match(hashtagPattern);
        if (!match || silent) {
            return false;
        }

        const tag = match[1];
        const href = `/search?q=%23${encodeURIComponent(tag)}&src=hashtag&f=latest`;

        const open = state.push('link_open', 'a', 1);
        open.attrs = [
            ['href', href],
            ['class', 'hashtag-link'],
            ['data-hashtag', tag],
        ];

        const text = state.push('text', '', 0);
        text.content = `#${tag}`;

        state.push('link_close', 'a', -1);
        state.pos += match[0].length;

        return true;
    });
}
