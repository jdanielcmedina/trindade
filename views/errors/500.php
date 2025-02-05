<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error</title>
    <style>
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 40px;
            color: #e2e8f0;
            background: #0f172a;
        }
        .error-container {
            max-width: 1000px;
            margin: 0 auto;
            background: #1e293b;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            border: 1px solid #334155;
        }
        h1 { 
            color: #f87171;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.8rem;
        }
        h1::before {
            content: "⚠️";
            font-size: 2rem;
        }
        .error-terminal {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            font-family: 'Fira Code', 'Cascadia Code', monospace;
        }
        .error-header {
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
        .error-type {
            color: #f87171;
            font-weight: 600;
        }
        .error-message {
            color: #e2e8f0;
            margin: 10px 0;
        }
        .error-location {
            color: #94a3b8;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .error-file {
            color: #818cf8;
        }
        .error-line {
            color: #34d399;
        }
        .error-trace {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #334155;
            white-space: pre-wrap;
            font-size: 0.9em;
            color: #94a3b8;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.2s;
            margin-top: 20px;
            font-size: 0.9em;
        }
        .back-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        .production-message {
            background: #172554;
            padding: 25px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #1e40af;
        }
        .production-message h2 {
            color: #60a5fa;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>System Error Detected</h1>

        <?php if (isset($error)): ?>
            <div class="error-terminal">
                <div class="error-header">
                    <div class="terminal-dot dot-red"></div>
                    <div class="terminal-dot dot-yellow"></div>
                    <div class="terminal-dot dot-green"></div>
                </div>

                <div class="error-type">
                    [ERROR] <?= htmlspecialchars($error['type']) ?>
                </div>

                <div class="error-message">
                    <?= htmlspecialchars($error['message']) ?>
                </div>

                <div class="error-location">
                    <span>at</span>
                    <span class="error-file"><?= htmlspecialchars($error['file']) ?></span>
                    <span>:</span>
                    <span class="error-line"><?= $error['line'] ?></span>
                </div>

                <?php if (isset($error['trace'])): ?>
                    <div class="error-trace">
                        <div style="color: #60a5fa;">Stack Trace:</div>
                        <?= htmlspecialchars($error['trace']) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="production-message">
                <h2>We're working on it!</h2>
                <p>Our team has been notified and we're looking into the issue. Please try again later.</p>
                <p>If the problem persists, please contact support.</p>
            </div>
        <?php endif; ?>

        <a href="/" class="back-btn">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Homepage
        </a>
    </div>
</body>
</html> 