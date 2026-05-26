<?php
// Global Cross-Origin (CORS) setup so Canva frames can fetch audio safely
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$statusMessage = '';
$generatedLink = '';

// Process file upload inside admin view dashboard execution loops
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['audioFile'])) {
    $file = $_FILES['audioFile'];
    $color = isset($_POST['colorPicker']) ? str_replace('#', '', $_POST['colorPicker']) : 'ff4757';
    
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($fileExtension !== 'mp3') {
        $statusMessage = '<span style="color: #ff4757;">Error: Only MP3 files are allowed.</span>';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $statusMessage = '<span style="color: #ff4757;">Error uploading file to storage array.</span>';
    } else {
        // Generate short timestamped identifier mapping (Saves string characters)
        $shortId = time();
        $uniqueFileName = $shortId . '_' . $color . '.mp3';
        $targetPath = $uploadDir . $uniqueFileName;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $basePath = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
            $basePath = rtrim($basePath, '/\\') . '/';
            
            // Ultra short template URL link matching Canva embedding policy constraints
            $generatedLink = $basePath . 'play/' . $shortId . '_' . $color;
            $statusMessage = '<span style="color: #2ed573;">Template URL Generated Successfully!</span>';
        } else {
            $statusMessage = '<span style="color: #ff4757;">Error saving file into directory array.</span>';
        }
    }
}

// Router parameters extraction checks to parse layout mode paths
$requestUri = $_SERVER['REQUEST_URI'];
$isCanvaMode = false;
$embedColor = 'ff4757';
$audioSource = '';

if (strpos($requestUri, '/play/') !== false) {
    $isCanvaMode = true;
    $parts = explode('/play/', $requestUri);
    $paramsString = end($parts); // format: timestamp_color (e.g., 1779819309_ff4757)
    
    $paramParts = explode('_', $paramsString);
    if (count($paramParts) >= 2) {
        $timestampId = $paramParts[0];
        $embedColor = htmlspecialchars($paramParts[1]);
        
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        // Reconstruct exact raw path pointers targeting asset uploads folder arrays
        $currentDir = dirname($_SERVER['REQUEST_URI']);
        if (strpos($currentDir, '/play') !== false) {
            $currentDir = substr($currentDir, 0, strpos($currentDir, '/play'));
        }
        $currentDir = rtrim($currentDir, '/\\') . '/';
        
        $audioSource = $protocol . $_SERVER['HTTP_HOST'] . $currentDir . 'uploads/' . $timestampId . '_' . $embedColor . '.mp3';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canva Audio Template Manager</title>
    <style>
        body {
            margin: 0; padding: 10px; display: flex; flex-direction: column;
            align-items: center; justify-content: center; height: 100vh;
            background: transparent; font-family: Arial, sans-serif;
        }
        .admin-dashboard {
            background: #ffffff; padding: 25px; border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15); width: 320px;
            display: flex; flex-direction: column; gap: 14px; color: #333;
            box-sizing: border-box;
        }
        .admin-dashboard h3 { margin: 0 0 5px 0; font-size: 16px; color: #111; }
        .admin-dashboard button {
            background: #0070f3; color: white; border: none; padding: 10px;
            border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 13px;
        }
        .link-box {
            background: #f4f4f4; padding: 8px; border-radius: 4px;
            font-size: 11px; word-break: break-all; border: 1px dashed #aaa;
            margin-top: 5px; user-select: all; font-family: monospace;
        }
        .play-btn {
            width: 65px; height: 65px; border-radius: 50%; border: none;
            background-color: #<?php echo $embedColor; ?>; color: white; font-size: 26px;
            cursor: pointer; display: flex; align-items: center;
            justify-content: center; box-shadow: 0 5px 12px rgba(0,0,0,0.2);
            outline: none; transition: transform 0.2s;
        }
        .play-btn:active { transform: scale(0.95); }
        audio { display: none; }
    </style>
</head>
<body>

    <?php if (!$isCanvaMode): ?>
        <div class="admin-dashboard">
            <h3>⚙️ Audio Widget Dashboard</h3>
            <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 14px;">
                <div>
                    <label style="font-size:12px; font-weight:bold; display:block;">Select MP3 File:</label>
                    <input type="file" name="audioFile" accept="audio/mp3" required>
                </div>
                
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <label style="font-size:12px; font-weight:bold;">Widget Accent Color:</label>
                    <input type="color" name="colorPicker" value="#ff4757">
                </div>
                
                <button type="submit">Upload & Create Template</button>
            </form>

            <?php if (!empty($statusMessage)): ?>
                <div style="font-size:12px; font-weight:bold; text-align:center; margin-top:5px;">
                    <?php echo $statusMessage; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($generatedLink)): ?>
                <div style="margin-top: 5px;">
                    <span style="font-size: 11px; font-weight: bold; color: #2ed573;">Copy this Link into Canva Embed:</span>
                    <div class="link-box"><?php echo $generatedLink; ?></div>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <button class="play-btn" id="customPlayBtn">▶</button>
        <audio id="myAudio" src="<?php echo $audioSource; ?>" preload="auto" crossorigin="anonymous"></audio>

        <script>
            const audio = document.getElementById('myAudio');
            const playBtn = document.getElementById('customPlayBtn');

            playBtn.addEventListener('click', () => {
                if (!audio.src || audio.src === window.location.href) {
                    alert("Error: Audio source file missing on server.");
                    return;
                }
                
                if (audio.paused) {
                    audio.play().then(() => {
                        playBtn.innerHTML = "⏸";
                    }).catch((err) => {
                        alert("Playback blocked. Make sure file exists on server.");
                        console.error(err);
                    });
                } else {
                    audio.pause();
                    playBtn.innerHTML = "▶";
                }
            });

            audio.addEventListener('ended', () => { playBtn.innerHTML = "▶"; });
        </script>
    <?php endif; ?>

</body>
</html>
