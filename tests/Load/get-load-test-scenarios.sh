#!/bin/bash
# Find all scenario script files and output their base names without extensions, one per line
# Exclude utility scripts like PrepareCustomers, CleanupCustomers, and insertCustomers
# Temporarily exclude getCustomers due to MongoDB serialization issue with readableLink
find ./tests/Load/scripts -name "*.js" ! -name "PrepareCustomers.js" ! -name "CleanupCustomers.js" ! -name "insertCustomers.js" ! -name "getCustomers.js" -exec basename {} .js \;
