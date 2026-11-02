<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="30">
    <title>SYSTEM BREACH — VAGABOND OVERRIDE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #050508;
            color: #f1f5f9;
            font-family: 'Chakra Petch', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow-x: hidden;
        }

        /* CRT Scanlines Overlay */
        .scanlines {
            position: fixed;
            inset: 0;
            background: linear-gradient(
                rgba(18, 16, 16, 0) 50%, 
                rgba(0, 0, 0, 0.4) 50%
            ), linear-gradient(
                90deg,
                rgba(255, 0, 0, 0.03),
                rgba(0, 255, 0, 0.01),
                rgba(0, 0, 255, 0.03)
            );
            background-size: 100% 4px, 6px 100%;
            pointer-events: none;
            z-index: 20;
        }

        /* Red Vignette Glow */
        .vignette {
            position: fixed;
            inset: 0;
            box-shadow: inset 0 0 120px rgba(220, 38, 38, 0.25);
            pointer-events: none;
            z-index: 21;
        }

        .container {
            position: relative;
            z-index: 30;
            max-width: 680px;
            width: 100%;
            background: rgba(10, 10, 14, 0.92);
            border: 2px solid #dc2626;
            box-shadow: 0 0 40px rgba(220, 38, 38, 0.3), inset 0 0 30px rgba(220, 38, 38, 0.15);
            border-radius: 8px;
            padding: 2.5rem 2rem;
            text-align: center;
            backdrop-filter: blur(10px);
            animation: glitch-flicker 4s infinite alternate;
        }

        @keyframes glitch-flicker {
            0%, 95% { opacity: 1; }
            96% { opacity: 0.8; transform: translate(1px, -1px); }
            97% { opacity: 1; transform: translate(0, 0); }
            98% { opacity: 0.7; transform: translate(-1px, 1px); }
            99%, 100% { opacity: 1; transform: translate(0, 0); }
        }

        /* Mask Container */
        .mask-wrapper {
            margin: 0 auto 1.5rem auto;
            width: 140px;
            height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .mask-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 0 25px rgba(220, 38, 38, 0.7));
            animation: pulse-aura 2.5s infinite ease-in-out;
        }

        @keyframes pulse-aura {
            0%, 100% { filter: drop-shadow(0 0 20px rgba(220, 38, 38, 0.6)) scale(1); }
            50% { filter: drop-shadow(0 0 35px rgba(239, 68, 68, 0.9)) scale(1.03); }
        }

        .alert-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(220, 38, 38, 0.2);
            border: 1px solid #ef4444;
            color: #f87171;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            padding: 6px 16px;
            border-radius: 4px;
            text-transform: uppercase;
            margin-bottom: 1.25rem;
        }

        .alert-dot {
            width: 8px;
            height: 8px;
            background-color: #ef4444;
            border-radius: 50%;
            box-shadow: 0 0 10px #ef4444;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.1; }
        }

        h1 {
            font-size: 2.2rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 0.25rem;
            text-shadow: 0 0 15px rgba(220, 38, 38, 0.8);
        }

        .demand-headline {
            font-size: 1.5rem;
            font-weight: 800;
            color: #ef4444;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 1.25rem;
            background: linear-gradient(90deg, #ef4444, #f87171, #ef4444);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 20px rgba(239, 68, 68, 0.5);
        }

        .message-body {
            font-size: 0.95rem;
            color: #cbd5e1;
            line-height: 1.7;
            margin-bottom: 1.75rem;
            text-align: justify;
            text-align-last: center;
            background: rgba(18, 18, 26, 0.6);
            border-left: 3px solid #dc2626;
            border-right: 3px solid #dc2626;
            padding: 1rem 1.25rem;
            border-radius: 4px;
        }

        .message-body strong {
            color: #fca5a5;
            font-weight: 700;
        }

        /* Terminal Log Block */
        .terminal {
            background: #000000;
            border: 1px solid #334155;
            border-radius: 6px;
            padding: 1rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            text-align: left;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 1.75rem;
            overflow-x: auto;
        }

        .terminal .red { color: #ef4444; font-weight: bold; }
        .terminal .yellow { color: #f59e0b; }
        .terminal .green { color: #10b981; }

        .ooc-note {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            color: #64748b;
            border-top: 1px dashed #334155;
            padding-top: 1rem;
            line-height: 1.5;
        }

        .ooc-note span {
            color: #94a3b8;
        }

        @media (max-width: 640px) {
            h1 { font-size: 1.6rem; }
            .demand-headline { font-size: 1.2rem; }
            .container { padding: 1.75rem 1.25rem; }
            .mask-wrapper { width: 110px; height: 110px; }
        }
    </style>
</head>
<body>
    <div class="scanlines"></div>
    <div class="vignette"></div>

    <div class="container">
        <!-- Vagabond Skull Mask -->
        <div class="mask-wrapper">
            <img src="/images/vagabond-mask.png" alt="Vagabond Mask" class="mask-img">
        </div>

        <!-- Hacker Alert Badge -->
        <div class="alert-pill">
            <span class="alert-dot"></span>
            SYSTEM BREACH • TRANSMISSION OVERRIDE
        </div>

        <h1>DISPATCH NETWORK HIJACKED</h1>
        <div class="demand-headline">BEBASKAN JORDAN KALUX!</div>

        <!-- Roleplay Story Message -->
        <div class="message-body">
            Salam untuk seluruh petinggi <strong>SASP, LSPD, BCSO</strong>, dan Penegak Hukum San Andreas.<br><br>
            Kalian pikir kalian bisa menahan saudara kami, <strong>Jordan Kalux</strong>, tanpa konsekuensi?
            Mulai detik ini, seluruh jaringan komunikasi satelit, kamera pantau lalu lintas, dan portal <strong>Police Multiview</strong> berada di bawah kuasa <strong>VAGABOND</strong>.
            <br><br>
            Kembalikan <strong>Jordan Kalux</strong> ke jalanan, atau jangan harap Los Santos akan kembali aman dan terang. <em>We are everywhere. We never forget.</em>
        </div>

        <!-- Terminal Status Output -->
        <div class="terminal">
            <div><span class="red">[CRITICAL]</span> SATELLITE DISPATCH FEED : <span class="red">DISRUPTED</span></div>
            <div><span class="yellow">[TARGET]</span> DETAINEE IDENTIFIER   : <span class="red">JORDAN KALUX (VAGABOND)</span></div>
            <div><span class="yellow">[DEMAND]</span> STATUS                 : <span class="yellow">AWAITING POLICE CONCESSION</span></div>
            <div><span class="green">[SYSTEM]</span> RECONNECT COUNTER      : <span class="green">AUTO-SYNC ACTIVE (30s)</span></div>
        </div>

        <!-- OOC Deployment Notice -->
        <div class="ooc-note">
            <span>[ OOC INFORMATION ]</span><br>
            Server sedang dalam proses pemeliharaan & deployment pembaruan sistem.<br>
            Halaman ini akan me-refresh secara otomatis setiap 30 detik.
        </div>
    </div>
</body>
</html>
