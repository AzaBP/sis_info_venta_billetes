<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Hola Julio! - TrainWeb</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0a2a66;
            --accent: #ff4757;
            --gradient: linear-gradient(135deg, #0a2a66 0%, #1e3799 100%);
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            background: var(--gradient);
            color: white;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            text-align: center;
        }

        .container {
            max-width: 600px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .icon-box {
            font-size: 4rem;
            margin-bottom: 20px;
            color: var(--accent);
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-20px);}
            60% {transform: translateY(-10px);}
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        p {
            font-size: 1.2rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 30px;
        }

        .btn-back {
            display: inline-block;
            padding: 12px 30px;
            background: var(--accent);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .btn-back:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 20px rgba(255, 71, 87, 0.4);
        }

        .train-animation {
            position: absolute;
            bottom: 20px;
            left: -100px;
            font-size: 2rem;
            animation: moveTrain 10s linear infinite;
        }

        @keyframes moveTrain {
            from { left: -100px; }
            to { left: 100%; }
        }

        .stars {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            background: url('https://www.transparenttextures.com/patterns/stardust.png');
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="stars"></div>
    
    <div class="container">
        <div class="icon-box">
            <i class="fa-solid fa-train-subway"></i>
        </div>
        <h1>¡Hola, Julio! 👋</h1>
        <p>
            Vemos que estás revisando la web a fondo.<br><br>
            Pero nos lo hemos currado bastante, en esta no nos has pillado.
        </p>
        <p style="font-style: italic; font-size: 1.4rem;">
            ¡¡Esperamos que te este gustando la web tanto como a nosotros tu asignatura!!
        </p>
        <a href="index.php" class="btn-back">Ir a una web top top</a>
    </div>

    <div class="train-animation">
        🚋🚋🚋
    </div>
</body>
</html>
