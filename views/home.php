<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <style>
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 40px;
            color: #e2e8f0;
            background: #0f172a;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #1e293b;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            border: 1px solid #334155;
        }
        h1, h2 { 
            color: #60a5fa;
            margin-top: 0;
        }
        .story {
            background: #172554;
            padding: 25px;
            border-left: 4px solid #3b82f6;
            margin: 30px 0;
            border-radius: 0 8px 8px 0;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .feature-card {
            background: #0f172a;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #334155;
            transition: transform 0.2s;
        }
        .feature-card:hover {
            transform: translateY(-2px);
        }
        .feature-card h3 {
            color: #60a5fa;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .terminal {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            font-family: 'Fira Code', 'Cascadia Code', monospace;
        }
        .terminal-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #334155;
        }
        .terminal-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        .dot-red { background: #ef4444; }
        .dot-yellow { background: #eab308; }
        .dot-green { background: #22c55e; }
        code {
            background: #1e293b;
            padding: 3px 6px;
            border-radius: 4px;
            font-family: monospace;
            color: #60a5fa;
        }
        .api-test {
            background: #172554;
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
            text-align: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.2s;
            margin: 5px;
            font-size: 0.9em;
        }
        .btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        .collaborate {
            background: #172554;
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
            text-align: center;
            border: 1px solid #334155;
        }
        .github-btn {
            background: #333;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
            transition: all 0.2s;
        }
        .github-btn:hover {
            background: #000;
            transform: translateY(-1px);
        }
        .documentation {
            margin-top: 30px;
            padding: 25px;
            background: #172554;
            border-radius: 8px;
            border: 1px solid #334155;
        }
        .doc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .doc-card {
            background: #0f172a;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #334155;
            transition: transform 0.2s;
        }
        .doc-card:hover {
            transform: translateY(-2px);
        }
        .doc-card h3 {
            color: #60a5fa;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to Trindade Framework</h1>
        
        <div class="story">
            <h2>The Story Behind Trindade</h2>
            <p>Hey there! 👋 I'm Daniel Medina, and Trindade Framework is my 10-year journey in PHP development. What started as a personal toolkit for my projects has grown into something I'm now excited to share with the community.</p>
            <p>As a passionate advocate for minimalist and functional code, I've always preferred writing clean, dependency-free solutions rather than relying on external libraries. Trindade reflects this philosophy - it's lightweight, straightforward, and gets the job done without unnecessary complexity.</p>
        </div>

        <div class="features">
            <div class="feature-card">
                <h3>🚀 Lightweight</h3>
                <p>No external dependencies, no bloat - just clean, efficient code that does exactly what you need.</p>
            </div>
            <div class="feature-card">
                <h3>⚡ Fast</h3>
                <p>Minimal overhead means better performance. Every line of code is purposeful.</p>
            </div>
            <div class="feature-card">
                <h3>🛠️ Practical</h3>
                <p>Built from real-world experience, solving real problems in production environments.</p>
            </div>
        </div>

        <div class="terminal">
            <div class="terminal-header">
                <div class="terminal-dot dot-red"></div>
                <div class="terminal-dot dot-yellow"></div>
                <div class="terminal-dot dot-green"></div>
            </div>
            <h2>Try These Examples:</h2>
            <ul>
                <li><code>GET /user/123</code> - Get user by ID</li>
                <li><code>GET /posts/2024/02/my-post</code> - Get post by year/month/slug</li>
                <li><code>GET /search/query?page=1&limit=10</code> - Search with pagination</li>
                <li><code>GET /products/1</code> - Get product</li>
                <li><code>GET /categories/5/products/123</code> - Get product in category</li>
            </ul>
        </div>

        <div class="api-test">
            <h2>API Test Routes</h2>
            <p>Experience the simplicity of our API versioning:</p>
            <p>
                <a href="/api/v1/test" class="btn">Test API v1</a>
                <a href="/api/v2/test" class="btn">Test API v2</a>
            </p>
        </div>

        <div class="collaborate">
            <h2>Let's Build Together!</h2>
            <p>After a decade of private development, Trindade is now open source. Whether you're a seasoned developer or just starting out, your contributions are welcome!</p>
            <p>Join me in making Trindade even better - every line of code, every suggestion, and every improvement counts.</p>
            <a href="https://github.com/jdanielcmedina/trindade" class="github-btn">
                <svg height="20" width="20" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/>
                </svg>
                Contribute on GitHub
            </a>
        </div>

        <div class="documentation">
            <div class="terminal">
                <div class="terminal-header">
                    <div class="terminal-dot dot-red"></div>
                    <div class="terminal-dot dot-yellow"></div>
                    <div class="terminal-dot dot-green"></div>
                </div>
                <h2>📚 Documentation</h2>
                <div class="doc-grid">
                    <a href="/docs/getting-started" class="doc-card">
                        <h3>🚀 Getting Started</h3>
                        <p>Quick start guide and basic concepts</p>
                    </a>
                    <a href="/docs/routing" class="doc-card">
                        <h3>🛣️ Routing</h3>
                        <p>Route handling and URL patterns</p>
                    </a>
                    <a href="/docs/database" class="doc-card">
                        <h3>💾 Database</h3>
                        <p>Database operations and models</p>
                    </a>
                    <a href="/docs/api" class="doc-card">
                        <h3>🔌 API Development</h3>
                        <p>Build RESTful APIs</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 