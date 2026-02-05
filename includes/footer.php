<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* --- FOOTER BACKLOGGD STYLE --- */
.bkd-footer {
    background-color: #161616; /* Fundo quase preto, igual ao header */
    border-top: 1px solid rgba(255, 255, 255, 0.1); /* Linha divisória subtil */
    padding: 35px 20px;
    margin-top: auto; /* Empurra o footer para o fundo da página */
    font-family: 'Inter', sans-serif;
    width: 100%;
    box-sizing: border-box;
}

.bkd-footer-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between; /* Espalha os elementos (Esq - Meio - Dir) */
    align-items: center;
    flex-wrap: wrap; /* Permite quebrar linha em telemóveis */
    gap: 20px;
}

/* Lado Esquerdo: Marca */
.bkd-footer-brand {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.bkd-logo-text {
    color: #fff;
    font-weight: 800;
    font-size: 1.1rem;
    letter-spacing: -0.5px;
}

.bkd-copyright {
    color: #666; /* Cinzento escuro para texto secundário */
    font-size: 0.8rem;
}

/* Centro: Links */
.bkd-footer-links {
    display: flex;
    gap: 25px;
}

.bkd-footer-links a {
    color: #999; /* Cinzento claro */
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: color 0.2s ease;
}

.bkd-footer-links a:hover {
    color: #fff; /* Fica branco ao passar o rato */
    text-decoration: underline;
}

/* Lado Direito: Social */
.bkd-footer-social {
    display: flex;
    gap: 20px;
}

.bkd-footer-social a {
    color: #888;
    font-size: 1.1rem;
    transition: all 0.2s ease;
}

.bkd-footer-social a:hover {
    color: #fff;
    transform: translateY(-2px); /* Pequeno salto ao passar o rato */
}

/* --- RESPONSIVO (TELEMÓVEL) --- */
@media (max-width: 768px) {
    .bkd-footer-container {
        flex-direction: column; /* Empilha tudo verticalmente */
        align-items: flex-start; /* Alinha à esquerda */
        gap: 30px;
    }

    .bkd-footer-links {
        flex-direction: column; /* Links um por baixo do outro */
        gap: 12px;
    }
    
    .bkd-footer-social {
        margin-top: 10px;
    }
}
</style>
<footer class="bkd-footer">
    <div class="bkd-footer-container">
        
        <div class="bkd-footer-brand">
            <span class="bkd-logo-text">GameList</span>
            <span class="bkd-copyright">&copy; 2026 GameList Inc. Todos os direitos reservados.</span>
        </div>

        <div class="bkd-footer-links">
            <a href="#">Sobre</a>
            <a href="#">Termos</a>
            <a href="#">Privacidade</a>
            <a href="#">API</a>
            <a href="#">Contactar</a>
        </div>

        <div class="bkd-footer-social">
            <a href="#" aria-label="Twitter"><i class="fa-brands fa-x-twitter"></i></a>
            <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
            <a href="#" aria-label="Discord"><i class="fa-brands fa-discord"></i></a>
        </div>

    </div>
</footer>
</body>
</html>