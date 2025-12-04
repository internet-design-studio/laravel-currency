import {defineConfig} from 'vitepress';

export default defineConfig({
  base: '/laravel-currency/',
  lang: 'en-US',
  title: 'Laravel Currency Package',
  titleTemplate: '%s',
  description: 'Laravel package for retrieve fiat and crypto currency exchange rates',
  cleanUrls: true,
  head: [['link', {rel: 'icon', type: 'image/x-icon', href: '/laravel-currency/favicon.ico'}]],
  themeConfig: {
    nav: [
      {text: 'Home', link: '/'},
      {text: 'Getting Started', link: '/index'},
      {text: 'Configuration', link: '/configuration'},
      {text: 'Adapters', link: '/adapters'},
      {text: 'Custom Adapters', link: '/custom-adapters'},
      {text: 'Testing', link: '/testing'},
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
      {
        text: 'Testing',
        items: [
          {text: 'Testing', link: '/testing'},
        ],
      },
    ],
    socialLinks: [
      {icon: 'github', link: 'https://github.com/internet-design-studio/laravel-currency'},
        {icon: {svg: '<svg id="svk" width="194" height="30" viewBox="0 0 97 15" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.01187 0.124801C12.4627 -0.562808 15.8237 1.65175 16.5196 5.07042C17.7576 11.1814 11.4172 14.8229 5.24998 12.76C6.18003 12.4081 6.88237 11.7496 7.04273 10.9684C4.91965 12.2597 2.24816 12.2758 0 11.204C1.4496 10.9425 4.34237 9.44142 4.83626 8.0888C3.6721 8.134 2.51434 8.6376 1.6773 9.47048C2.11025 6.74264 4.42576 4.8735 6.82144 4.37958C6.08381 3.81465 4.77212 3.91795 4.16599 4.49257C4.82023 2.33613 6.6226 0.599348 9.01187 0.124801Z" fill="#FFB301"/></svg>'}, link: 'https://www.internet-design.ru'}
    ],
    editLink: {
      pattern: 'https://github.com/internet-design-studio/laravel-currency/tree/master/docs/:path',
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
