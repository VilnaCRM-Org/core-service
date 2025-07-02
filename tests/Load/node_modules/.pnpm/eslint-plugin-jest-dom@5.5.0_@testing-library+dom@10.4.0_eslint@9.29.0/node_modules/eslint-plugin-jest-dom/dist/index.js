"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");
Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.rules = exports.default = exports.configs = void 0;
var _requireindex = _interopRequireDefault(require("requireindex"));
var _package = require("../package.json");
/**
 * @fileoverview lint rules for use with jest-dom
 * @author Ben Monro
 */

//------------------------------------------------------------------------------
// Requirements
//------------------------------------------------------------------------------

//------------------------------------------------------------------------------
// Plugin Definition
//------------------------------------------------------------------------------

// import all rules in src/rules and re-export them for .eslintrc configs
const rules = exports.rules = (0, _requireindex.default)(`${__dirname}/rules`);
const allRules = Object.entries(rules).reduce((memo, [name]) => ({
  ...memo,
  ...{
    [`jest-dom/${name}`]: "error"
  }
}), {});
const recommendedRules = allRules;
const plugin = {
  meta: {
    name: _package.name,
    version: _package.version
  },
  configs: {
    recommended: {
      plugins: ["jest-dom"],
      rules: recommendedRules
    },
    all: {
      plugins: ["jest-dom"],
      rules: allRules
    }
  },
  rules
};
plugin.configs["flat/recommended"] = {
  plugins: {
    "jest-dom": plugin
  },
  rules: recommendedRules
};
plugin.configs["flat/all"] = {
  plugins: {
    "jest-dom": plugin
  },
  rules: allRules
};
var _default = exports.default = plugin; // explicitly export config to allow using this plugin in CJS-based
// eslint.config.js files without needing to deal with the .default
// and also retain backwards compatibility with `.eslintrc` configs
const configs = exports.configs = plugin.configs;