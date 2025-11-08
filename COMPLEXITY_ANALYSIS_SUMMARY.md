# Complexity Analysis Tools - Quick Reference

## What Was Added

### 1. PHPMetrics Package
Installed `phpmetrics/phpmetrics` for advanced code metrics analysis.

### 2. Custom Complexity Analysis Script
Created `scripts/analyze-complexity.php` and `scripts/analyze-complexity.sh` that:
- Uses **PHPMetrics** for professional static analysis
- Parses PHPMetrics JSON output
- Ranks classes by cyclomatic complexity (CCN)
- Provides additional metrics: WMC, LLOC, Maintainability Index
- Outputs top N most complex classes

### 3. Make Commands
Added three new commands to Makefile:

```bash
# Analyze top 20 classes (default) in human-readable format
make analyze-complexity

# Analyze custom number of classes
make analyze-complexity N=10

# Export to JSON
make analyze-complexity-json N=20

# Export to CSV
make analyze-complexity-csv N=20
```

### 4. Documentation
Created comprehensive guides:
- `COMPLEXITY_REFACTORING_GUIDE.md` - Detailed refactoring guide (9KB)
- `REFACTOR_COMPLEXITY.md` - Concise agent prompt (4KB)
- `COMPLEXITY_ANALYSIS_SUMMARY.md` - This file

## Quick Start for Code Agents

### Step 1: Analyze Complexity
```bash
make analyze-complexity N=20
```

### Step 2: Read the Refactoring Prompt
```bash
cat REFACTOR_COMPLEXITY.md
```

### Step 3: Start Refactoring
Follow the systematic workflow in the prompt to refactor each complex class.

## How the Analysis Works

The script uses **PHPMetrics** - a professional static analysis tool - to calculate accurate complexity metrics:

### Metrics Collected

**Cyclomatic Complexity (CCN)**
- Total number of decision points in the class
- Includes: if, else, for, foreach, while, case, catch, try, ternary operators
- Higher CCN = more complex code

**Weighted Method Count (WMC)**
- Sum of complexity across all methods
- Indicates overall class complexity

**Max Method Complexity**
- Highest cyclomatic complexity of any single method
- Helps identify the most complex method that needs refactoring

**Maintainability Index**
- Holistic metric combining complexity, volume, and other factors
- Scale: 0-100 (higher is better, > 65 is good)
- Below 20 indicates difficult-to-maintain code

## Example Output

```
╔════════════════════════════════════════════════════════════════════════════╗
║                TOP 10 MOST COMPLEX CLASSES (PHPMetrics)                     ║
╚════════════════════════════════════════════════════════════════════════════╝

#1 - App\Core\Customer\Application\Factory\CustomerUpdateFactory
──────────────────────────────────────────────────────────────────────────────
  🔢 Cyclomatic Complexity (CCN):    10
  🎯 Weighted Method Count (WMC):    12
  📊 Methods:                        3
  📏 Logical Lines of Code (LLOC):   21
  ⚡ Avg Complexity per Method:       3.33
  🔴 Max Method Complexity:          8
  💚 Maintainability Index:          94.90
```

## Key Metrics

- **CCN (Cyclomatic Complexity)**: Total decision points in all class methods
- **WMC (Weighted Method Count)**: Sum of complexity across all methods  
- **Methods**: Number of methods in the class
- **LLOC**: Logical lines of code (executable code only)
- **Avg Complexity**: CCN ÷ Methods (target: < 5)
- **Max Method Complexity**: Highest complexity of any single method
- **Maintainability Index**: 0-100 score (> 65 is good, < 20 is problematic)

## Target Standards

Per PHPInsights requirements:
- **Average complexity per method: < 5**
- **Min complexity score: 95%**

## Integration with Existing Tools

This complements existing quality tools:
- **PHPMD**: Identifies specific complexity violations
- **PHPInsights**: Enforces complexity thresholds
- **Complexity Analysis Script**: Provides overview and prioritization

### Recommended Workflow

1. `make analyze-complexity` - Get overview of complexity hotspots
2. `make phpmd` - Get detailed violations per method
3. Refactor identified classes
4. `make ci` - Verify all quality checks pass

## Files Modified/Created

### New Files
- `scripts/analyze-complexity.php` - PHP parser for PHPMetrics JSON
- `scripts/analyze-complexity.sh` - Bash wrapper script
- `COMPLEXITY_REFACTORING_GUIDE.md` - Detailed guide
- `REFACTOR_COMPLEXITY.md` - Agent prompt
- `COMPLEXITY_ANALYSIS_SUMMARY.md` - This summary

### Modified Files
- `Makefile` - Added 3 new commands
- `composer.json` - Added phpmetrics dependency
- `composer.lock` - Updated dependencies

## Usage Tips

### For Human Developers
```bash
# Get quick overview
make analyze-complexity N=10

# Export for analysis in spreadsheet
make analyze-complexity-csv N=50 > complexity-report.csv
```

### For Code Agents
```bash
# Get programmatic access
make analyze-complexity-json N=20 > complexity.json

# Parse and process the JSON output
```

### For CI/CD
```bash
# Track complexity over time
make analyze-complexity-json N=100 > reports/complexity-$(date +%Y%m%d).json
```

## Next Steps

For code agents tasked with refactoring:
1. Read `REFACTOR_COMPLEXITY.md` for complete instructions
2. Run `make analyze-complexity N=20`
3. Follow the systematic refactoring workflow
4. Maintain 100% test coverage and quality standards

For detailed refactoring patterns and examples, see `COMPLEXITY_REFACTORING_GUIDE.md`.
