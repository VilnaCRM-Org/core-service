"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getScope = getScope;
exports.getSourceCode = getSourceCode;
/* istanbul ignore next */
function getSourceCode(context) {
  if ('sourceCode' in context) {
    return context.sourceCode;
  }
  return context.getSourceCode();
}

/* istanbul ignore next */
function getScope(context, node) {
  const sourceCode = getSourceCode(context);
  if (sourceCode && sourceCode.getScope) {
    return sourceCode.getScope(node);
  }
  return context.getScope();
}