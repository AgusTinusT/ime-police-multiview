import vue from "eslint-plugin-vue";
import unusedImports from "eslint-plugin-unused-imports";
import vueParser from "vue-eslint-parser";

export default [
    {
        files: ["resources/js/**/*.{js,vue}"],
        languageOptions: {
            parser: vueParser,
            ecmaVersion: "latest",
            sourceType: "module",
        },
        plugins: {
            vue,
            "unused-imports": unusedImports,
        },
        rules: {
            "no-unused-vars": "off",
            "unused-imports/no-unused-imports": "error",
            "unused-imports/no-unused-vars": [
                "warn",
                {
                    vars: "all",
                    varsIgnorePattern: "^_",
                    args: "after-used",
                    argsIgnorePattern: "^_",
                },
            ],
            "vue/multi-word-component-names": "off",
            "vue/no-v-html": "off",
        },
    },
];
