<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');

    :root {
        --bg-footer: #0d1117;
        --text-header: #7d8590;
        --text-link: #cfd7e6;
        --accent-glow: #a855f7; /* Roxo */
    }

    footer {
        position: relative; /* Necessário para o fundo absoluto */
        background-color: var(--bg-footer);
        color: var(--text-link);
        font-family: 'Inter', sans-serif;
        padding: 60px 20px 20px;
        margin-top: 80px;
        font-size: 14px;
        overflow: hidden; /* Corta os ícones que saem da área */
    }

    /* --- CAMADA DE FUNDO (A chuva de ícones) --- */
    .footer-background {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0; /* Fica atrás de tudo */
        pointer-events: none; /* O rato ignora isto, deixa clicar nos links */
        overflow: hidden;
    }

    /* Máscara para suavizar a entrada e saída (Opcional, mas fica pro) */
    .footer-background::after {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: linear-gradient(to bottom, var(--bg-footer) 0%, transparent 20%, transparent 80%, var(--bg-footer) 100%);
        z-index: 1;
    }

    .bg-icon {
        position: absolute;
        top: -50px; /* Começa escondido em cima */
        color: var(--accent-glow);
        opacity: 0;
        animation: fallDown linear infinite;
    }

    /* Animação de Cair (Matrix Style) */
    @keyframes fallDown {
        0% {
            transform: translateY(0) rotate(0deg);
            opacity: 0;
        }
        10% {
            opacity: 0.15; /* Opacidade baixa para não atrapalhar a leitura */
        }
        90% {
            opacity: 0.15;
        }
        100% {
            transform: translateY(400px) rotate(360deg); /* Ajuste conforme altura do footer */
            opacity: 0;
        }
    }

    /* Distribuição Aleatória dos Ícones pelo Footer Inteiro */
    .i1 { left: 5%; font-size: 20px; animation-duration: 8s; animation-delay: 0s; }
    .i2 { left: 15%; font-size: 30px; animation-duration: 12s; animation-delay: 2s; }
    .i3 { left: 25%; font-size: 15px; animation-duration: 7s; animation-delay: 4s; }
    .i4 { left: 35%; font-size: 25px; animation-duration: 10s; animation-delay: 1s; }
    .i5 { left: 45%; font-size: 18px; animation-duration: 9s; animation-delay: 3s; }
    .i6 { left: 55%; font-size: 22px; animation-duration: 11s; animation-delay: 5s; }
    .i7 { left: 65%; font-size: 28px; animation-duration: 8.5s; animation-delay: 0.5s; }
    .i8 { left: 75%; font-size: 16px; animation-duration: 7.5s; animation-delay: 2.5s; }
    .i9 { left: 85%; font-size: 24px; animation-duration: 9.5s; animation-delay: 1.5s; }
    .i10 { left: 95%; font-size: 20px; animation-duration: 10.5s; animation-delay: 3.5s; }


    /* --- CAMADA DE CONTEÚDO (Links e Texto) --- */
    .footer-container {
        position: relative;
        z-index: 2; /* Fica à frente da animação */
        max-width: 1280px;
        margin: 0 auto;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 40px;
        padding-bottom: 50px;
    }

    .links-section {
        flex: 1; /* Ocupa tudo agora que não há bloco direito */
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); 
        gap: 30px;
    }

    .footer-col h4 {
        color: var(--text-header);
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 20px;
        text-transform: uppercase;
    }

    .footer-col ul {
        list-style: none !important;
        padding: 0 !important;
        margin: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 12px;
    }

    .footer-col ul li a {
        color: var(--text-link);
        text-decoration: none;
        transition: 0.2s;
        display: block;
        /* Adicionar um fundo subtil ao passar o rato ajuda a leitura */
        padding: 2px 0;
    }

    .footer-col ul li a:hover {
        color: #fff;
        text-decoration: underline;
        text-shadow: 0 0 10px rgba(0,0,0,0.8); /* Sombra para ler melhor se passar ícone atrás */
    }

    /* Footer Bottom */
    .footer-bottom {
        position: relative;
        z-index: 2;
        max-width: 1280px;
        margin: 0 auto;
        padding-top: 30px;
        border-top: 1px solid rgba(48, 54, 61, 0.5); /* Borda mais transparente */
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
        font-size: 12px;
        color: #7d8590;
    }

    .social-icons-bar a { color: #7d8590; font-size: 18px; margin-left: 20px; transition: 0.3s; }
    .social-icons-bar a:hover { color: #fff; }
</style>

<footer>
    <div class="footer-background">
        <i class="fa-solid fa-gamepad bg-icon i1"></i>
        <i class="fa-solid fa-ghost bg-icon i2"></i>
        <i class="fa-solid fa-bolt bg-icon i3"></i>
        <i class="fa-solid fa-heart bg-icon i4"></i>
        <i class="fa-solid fa-puzzle-piece bg-icon i5"></i>
        <i class="fa-solid fa-headset bg-icon i6"></i>
        <i class="fa-brands fa-playstation bg-icon i7"></i>
        <i class="fa-brands fa-xbox bg-icon i8"></i>
        <i class="fa-solid fa-trophy bg-icon i9"></i>
        <i class="fa-solid fa-rocket bg-icon i10"></i>
    </div>

    <div class="footer-container">
        
        <div class="links-section">
            <div class="footer-col">
                <h4>Produto</h4>
                <ul>
                    <li><a href="#">Jogos</a></li>
                    <li><a href="#">Reviews</a></li>
                    <li><a href="#">Top Rated</a></li>
                    <li><a href="#">Lançamentos</a></li>
                </ul>
            </div>
            
            <div class="footer-col">
                <h4>Plataforma</h4>
                <ul>
                    <li><a href="#">Developer API</a></li>
                    <li><a href="#">Parceiros</a></li>
                    <li><a href="#">Educação</a></li>
                    <li><a href="#">Mobile App</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Suporte</h4>
                <ul>
                    <li><a href="#">Docs</a></li>
                    <li><a href="#">Fórum</a></li>
                    <li><a href="#">Status</a></li>
                    <li><a href="#">Contactar</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Empresa</h4>
                <ul>
                    <li><a href="#">Sobre</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Carreiras</a></li>
                    <li><a href="#">Loja</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <div>&copy; 2025 GameList Inc.</div>
        <div class="social-icons-bar">
            <a href="#"><i class="fa-brands fa-x-twitter"></i></a>
            <a href="#"><i class="fa-brands fa-instagram"></i></a>
            <a href="#"><i class="fa-brands fa-discord"></i></a>
        </div>
    </div>
</footer>