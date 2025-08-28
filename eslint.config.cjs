const {
    defineConfig,
    globalIgnores,
} = require("eslint/config");

const prettier = require("eslint-plugin-prettier");
const js = require("@eslint/js");

const {
    FlatCompat,
} = require("@eslint/eslintrc");

const compat = new FlatCompat({
    baseDirectory: __dirname,
    recommendedConfig: js.configs.recommended,
    allConfig: js.configs.all
});

module.exports = defineConfig([{
    extends: compat.extends("eslint:recommended", "plugin:vue/vue3-recommended", "prettier"),

    plugins: {
        prettier,
    },

    rules: {
        "vue/camelcase": ["error"],
        "vue/require-v-for-key": ["error"],
        "vue/no-unused-properties": ["error"],
        "vue/no-v-html": "off",
        "vue/multi-word-component-names": "off",
        "prettier/prettier": ["error"],
        "vue/require-default-prop": "off",
        "vue/singleline-html-element-content-newline": 0,
        "vue/component-name-in-template-casing": ["error"],
        "vue/attribute-hyphenation": "off",
        "vue/no-multi-spaces": ["error"],
    },
}, globalIgnores(["resources/js/components/ui/*"])]);
