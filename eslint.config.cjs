const { defineConfig, globalIgnores } = require('eslint/config');
const js = require('@eslint/js');
const vue = require('eslint-plugin-vue');
const prettierRecommended = require('eslint-plugin-prettier/recommended');
const globals = require('globals');

module.exports = defineConfig([
  globalIgnores(['resources/js/components/ui/*']),
  js.configs.recommended,
  ...vue.configs['flat/recommended'],
  prettierRecommended,
  {
    languageOptions: {
      globals: globals.browser,
    },
    rules: {
      'vue/camelcase': ['error'],
      'vue/require-v-for-key': ['error'],
      'vue/no-unused-properties': ['error'],
      'vue/no-v-html': 'off',
      'vue/multi-word-component-names': 'off',
      'vue/require-default-prop': 'off',
      'vue/singleline-html-element-content-newline': 0,
      'vue/component-name-in-template-casing': ['error'],
      'vue/attribute-hyphenation': 'off',
      'vue/no-multi-spaces': ['error'],
    },
  },
]);
