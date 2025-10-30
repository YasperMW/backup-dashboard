# How to View the Use Case Diagrams

## Quick View Options

### 1. Online PlantUML Server (Easiest)
Visit this URL to see the detailed diagram instantly:
```
http://www.plantuml.com/plantuml/uml/
```
Then copy and paste the content from `use-case-diagram.puml` or `use-case-diagram-simple.puml`

### 2. PlantText (No Installation Required)
1. Go to https://www.planttext.com/
2. Paste the content from the `.puml` file
3. Click "Refresh" to render

### 3. VS Code Extension
1. Install "PlantUML" extension by jebbs
2. Open any `.puml` file
3. Press `Alt+D` (Windows/Linux) or `Option+D` (Mac)
4. Or right-click and select "Preview Current Diagram"

### 4. IntelliJ IDEA / PyCharm
1. Install "PlantUML integration" plugin
2. Open the `.puml` file
3. The preview should appear automatically on the right

### 5. Command Line (Requires Java + PlantUML)

#### Install PlantUML
**Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install plantuml
```

**macOS:**
```bash
brew install plantuml
```

**Windows:**
Download from https://plantuml.com/download

#### Generate Images
```bash
# Navigate to the docs directory
cd docs

# Generate PNG
plantuml use-case-diagram.puml

# Generate SVG (better quality)
plantuml -tsvg use-case-diagram.puml

# Generate both diagrams
plantuml *.puml
```

### 6. Online Diagram Viewer (Direct Link)
You can create a direct link by encoding the diagram. Use this tool:
https://plantuml-editor.kkeisuke.com/

## Recommended Workflow

For **quick viewing**: Use PlantText (Option 2)
For **development**: Use VS Code extension (Option 3)
For **documentation**: Generate SVG files (Option 5)
For **presentations**: Generate PNG files (Option 5)

## Troubleshooting

### "Cannot find Java"
PlantUML requires Java. Install it:
```bash
# Ubuntu/Debian
sudo apt-get install default-jre

# macOS
brew install openjdk
```

### "Diagram too large"
Use the simplified version: `use-case-diagram-simple.puml`

### "Cannot render in browser"
Some browsers block PlantUML rendering. Try:
1. Use Chrome or Firefox
2. Use the online editors listed above
3. Generate static images with command line

## Exporting for Documentation

### Generate High-Quality Images
```bash
# SVG (scalable, best for web)
plantuml -tsvg use-case-diagram.puml

# PNG with high DPI
plantuml -tpng use-case-diagram.puml

# PDF (best for printing)
plantuml -tpdf use-case-diagram.puml
```

### Include in Markdown
```markdown
![Use Case Diagram](use-case-diagram.svg)
```

### Include in LaTeX
```latex
\includegraphics[width=\textwidth]{use-case-diagram.pdf}
```

## Files Generated

After running PlantUML, you'll get:
- `use-case-diagram.png` or `.svg` - Detailed diagram
- `use-case-diagram-simple.png` or `.svg` - Simplified diagram

These can be committed to the repository for easy viewing on GitHub.
