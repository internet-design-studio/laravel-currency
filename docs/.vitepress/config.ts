import {defineConfig} from 'vitepress';

export default defineConfig({
  lang: 'en-US',
  title: 'Laravel CurrencyEnum Package',
  titleTemplate: '%s | Laravel CurrencyEnum Package',
  description: 'Extensible Laravel package for working with fiat and crypto currency exchange rates',
  cleanUrls: true,
  head: [['link', {rel: 'icon', type: 'image/x-icon', href: '/favicon.ico'}]],
  themeConfig: {
    nav: [
      {text: 'Home', link: '/'},
      {text: 'Getting Started', link: '/index'},
      {text: 'Configuration', link: '/configuration'},
      {text: 'Adapters', link: '/adapters'},
      {text: 'Custom Adapters', link: '/custom-adapters'},
    ],
    sidebar: [
      {
        text: 'Introduction',
        items: [
          {text: 'Getting Started', link: '/index'},
        ],
      },
      {
        text: 'Configuration',
        items: [
          {text: 'Configuration', link: '/configuration'},
        ],
      },
      {
        text: 'Adapters',
        items: [
          {text: 'Built-in Adapters', link: '/adapters'},
          {text: 'Custom Adapters', link: '/custom-adapters'},
        ],
      },
    ],
    socialLinks: [
      {icon: 'github', link: 'https://github.com/internet-design-studio/laravel-currency'},
    ],
    editLink: {
      pattern: 'https://github.com/internet-design-studio/laravel-currency/tree/main/docs/:path',
      text: 'Edit this page on GitHub',
    },
    outline: {
      level: [2, 3],
      label: 'On this page',
    },
    docFooter: {
      prev: 'Previous page',
      next: 'Next page',
    },
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © Internet Design Studio',
    },
  },
});
