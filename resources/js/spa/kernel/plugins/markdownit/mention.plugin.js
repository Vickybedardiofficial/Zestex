export default function (md) {
    md.inline.ruler.push('mention', (state, silent) => {
        const mentionPattern = /^@([a-zA-Z0-9_.]+)/;
        const pos = state.pos;
        const src = state.src;

        if (src.charAt(pos) !== '@') {
            return false;
        }

        // Avoid matching mentions inside words/emails like test@john
        if (pos > 0 && /[A-Za-z0-9_.]/.test(src.charAt(pos - 1))) {
            return false;
        }

        const match = src.slice(pos).match(mentionPattern);
        if (!match || silent) {
            return false;
        }

        const username = match[1];
        const mentionLink = `/@${username}`;
        const isBotMention = username.toLowerCase() === 'ze';

        const token = state.push('link_open', 'a', 1);
        token.attrs = [
            ['href', mentionLink],
            ['class', isBotMention ? 'mention-link mention-bot' : 'mention-link'],
            ['data-mention', username]
        ];
        token.content = `@${username}`;
        token.markup = '@';

        const textToken = state.push('text', '', 0);
        textToken.content = `@${username}`;

        state.push('link_close', 'a', -1);
        state.pos += match[0].length;

        return true;
    });
}

