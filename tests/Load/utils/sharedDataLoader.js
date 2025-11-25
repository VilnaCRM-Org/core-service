/* global open */

/**
 * Attempts to load a JSON fixture shared across load-test scripts regardless of script location.
 *
 * @param {string|undefined} fileLocation Base directory (may be absolute inside the container).
 * @param {string} fileName Target file name.
 * @param {string} description Short description for error context.
 *
 * @returns {object|array} Parsed JSON content.
 *
 * @throws {Error} When none of the candidate paths exist or contain valid JSON.
 */
export function loadSharedJsonFile(fileLocation, fileName, description) {
  const candidatePaths = buildCandidatePaths(fileLocation, fileName);
  const errorMessages = [];

  for (const path of candidatePaths) {
    try {
      return JSON.parse(open(path));
    } catch (error) {
      errorMessages.push(`${path}: ${error.message}`);
    }
  }

  throw new Error(
    `Unable to load ${description}. Tried paths: ${candidatePaths.join(
      ', '
    )}. Last error: ${errorMessages.pop()}`
  );
}

function buildCandidatePaths(fileLocation, fileName) {
  const candidateSet = new Set();

  if (fileLocation) {
    const normalizedLocation = fileLocation.endsWith('/')
      ? fileLocation
      : `${fileLocation}/`;
    candidateSet.add(`${normalizedLocation}${fileName}`);
  }

  ['../', '../../', '../../../', './', ''].forEach(prefix => {
    const resolvedPath = prefix ? `${prefix}${fileName}` : fileName;
    candidateSet.add(resolvedPath);
  });

  return Array.from(candidateSet);
}
