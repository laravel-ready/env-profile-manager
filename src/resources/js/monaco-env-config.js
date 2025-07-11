// Monaco Editor configuration for .env files
export const envLanguageDefinition = {
    id: 'env',
    extensions: ['.env'],
    aliases: ['Environment', 'env', 'dotenv'],
    mimetypes: ['text/x-env', 'text/plain']
};

export const envMonarchLanguage = {
    tokenizer: {
        root: [
            // Comments
            [/#.*$/, 'comment'],
            
            // Environment variable definitions
            [/^(\s*)([A-Za-z_][A-Za-z0-9_]*)(=)/, ['', 'variable.name', 'delimiter']],
            
            // Quoted strings
            [/"([^"\\]|\\.)*$/, 'string.invalid'],  // non-terminated string
            [/'([^'\\]|\\.)*$/, 'string.invalid'],  // non-terminated string
            [/"/, 'string', '@string_double'],
            [/'/, 'string', '@string_single'],
            
            // Numbers
            [/\d+/, 'number'],
            
            // Boolean-like values
            [/\b(true|false|yes|no|on|off)\b/i, 'keyword'],
            
            // URLs
            [/https?:\/\/[^\s]+/, 'string.link'],
            
            // Unquoted values
            [/[^\s#]+/, 'string.value']
        ],
        
        string_double: [
            [/[^\\"]+/, 'string'],
            [/\\./, 'string.escape'],
            [/"/, 'string', '@pop']
        ],
        
        string_single: [
            [/[^\\']+/, 'string'],
            [/\\./, 'string.escape'],
            [/'/, 'string', '@pop']
        ]
    }
};

export const envThemeRules = {
    light: [
        { token: 'comment', foreground: '6A737D' },
        { token: 'variable.name', foreground: '22863A', fontStyle: 'bold' },
        { token: 'delimiter', foreground: 'D73A49' },
        { token: 'string', foreground: '032F62' },
        { token: 'string.escape', foreground: '22863A' },
        { token: 'string.value', foreground: '005CC5' },
        { token: 'string.link', foreground: '0366D6', fontStyle: 'underline' },
        { token: 'number', foreground: '005CC5' },
        { token: 'keyword', foreground: 'D73A49' },
        { token: 'string.invalid', foreground: 'B31D28', fontStyle: 'bold' }
    ],
    dark: [
        { token: 'comment', foreground: '8B949E' },
        { token: 'variable.name', foreground: '7EE787', fontStyle: 'bold' },
        { token: 'delimiter', foreground: 'FF7B72' },
        { token: 'string', foreground: 'A5D6FF' },
        { token: 'string.escape', foreground: '7EE787' },
        { token: 'string.value', foreground: '79C0FF' },
        { token: 'string.link', foreground: '58A6FF', fontStyle: 'underline' },
        { token: 'number', foreground: '79C0FF' },
        { token: 'keyword', foreground: 'FF7B72' },
        { token: 'string.invalid', foreground: 'F85149', fontStyle: 'bold' }
    ]
};

export function registerEnvLanguage(monaco) {
    // Register the language
    monaco.languages.register(envLanguageDefinition);
    
    // Set the language configuration
    monaco.languages.setLanguageConfiguration('env', {
        comments: {
            lineComment: '#'
        },
        brackets: [
            ['{', '}'],
            ['[', ']'],
            ['(', ')']
        ],
        autoClosingPairs: [
            { open: '{', close: '}' },
            { open: '[', close: ']' },
            { open: '(', close: ')' },
            { open: '"', close: '"' },
            { open: "'", close: "'" }
        ],
        surroundingPairs: [
            { open: '{', close: '}' },
            { open: '[', close: ']' },
            { open: '(', close: ')' },
            { open: '"', close: '"' },
            { open: "'", close: "'" }
        ]
    });
    
    // Set the tokenizer
    monaco.languages.setMonarchTokensProvider('env', envMonarchLanguage);
}

export function registerEnvThemes(monaco) {
    // Register light theme
    monaco.editor.defineTheme('env-light', {
        base: 'vs',
        inherit: true,
        rules: envThemeRules.light,
        colors: {
            'editor.foreground': '#24292E',
            'editor.background': '#FFFFFF',
            'editor.lineHighlightBackground': '#F6F8FA',
            'editorLineNumber.foreground': '#959DA5',
            'editor.selectionBackground': '#C8E1FF',
            'editor.inactiveSelectionBackground': '#E1E4E8'
        }
    });
    
    // Register dark theme
    monaco.editor.defineTheme('env-dark', {
        base: 'vs-dark',
        inherit: true,
        rules: envThemeRules.dark,
        colors: {
            'editor.foreground': '#C9D1D9',
            'editor.background': '#0D1117',
            'editor.lineHighlightBackground': '#161B22',
            'editorLineNumber.foreground': '#8B949E',
            'editor.selectionBackground': '#264F78',
            'editor.inactiveSelectionBackground': '#264F7877'
        }
    });
}