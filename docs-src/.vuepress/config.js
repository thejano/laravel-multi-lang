import { defineUserConfig } from 'vuepress';
import { viteBundler } from '@vuepress/bundler-vite';
import { defaultTheme } from '@vuepress/theme-default';

export default defineUserConfig({
  lang: 'en-US',
  title: 'Laravel Multi-Lang',
  description:
    'Polymorphic translations for Laravel models with caching, helpers, CLI tooling, and documentation.',
  dest: 'docs',
  head: [
    ['meta', { name: 'theme-color', content: '#0f172a' }],
    ['link', { rel: 'icon', href: 'https://avatars.githubusercontent.com/u/108210034?v=4' }],
  ],
  theme: defaultTheme({
    logo: 'https://avatars.githubusercontent.com/u/108210034?v=4',
    repo: 'thejano/laravel-multi-lang',
    docsDir: 'docs-src',
    editLink: false,
    lastUpdated: true,
    navbar: [
      { text: 'Overview', link: '/guide/overview.html' },
      {
        text: 'Core',
        children: [
          '/guide/getting-started.md',
          '/guide/core-concepts.md',
          '/guide/querying.md',
          '/guide/managing-translations.md',
          '/guide/caching-performance.md',
        ],
      },
      {
        text: 'Automation',
        children: [
          '/guide/automation.md',
          '/guide/extensibility.md',
          '/guide/testing.md',
        ],
      },
    ],
    sidebar: {
      '/guide/': [
        {
          text: 'Start Here',
          children: [
            '/guide/overview.md',
            '/guide/getting-started.md',
            '/guide/core-concepts.md',
          ],
        },
        {
          text: 'Working with Translations',
          children: [
            '/guide/querying.md',
            '/guide/managing-translations.md',
            '/guide/middleware.md',
            '/guide/caching-performance.md',
          ],
        },
        {
          text: 'Operations & Scale',
          children: [
            '/guide/automation.md',
            '/guide/extensibility.md',
            '/guide/testing.md',
          ],
        },
      ],
    },
  }),
  bundler: viteBundler(),
});

