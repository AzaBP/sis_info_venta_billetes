document.addEventListener('DOMContentLoaded', () => {
    const translations = {
        es: {
            page_title: 'TrainWeb - Página de Trenes',
            nav_billetes: 'Billetes',
            nav_ofertas: 'Ofertas',
            nav_ayuda: 'Ayuda',
            nav_idiomas: 'Idiomas',
            lang_es: 'Español',
            lang_en: 'Inglés',
            lang_fr: 'Francés',
            lang_de: 'Alemán',
            login_button: 'Iniciar sesión',
            hero_title: 'Busca tu tren',
            trip_oneway: 'Solo ida',
            trip_roundtrip: 'Ida y vuelta',
            origin_placeholder: 'Origen',
            destination_placeholder: 'Destino',
            passengers_1: '1 pasajero',
            passengers_2: '2 pasajeros',
            passengers_3: '3 pasajeros',
            passengers_4: '4 pasajeros',
            search_button: 'Buscar billetes',
            popular_title: 'Destinos Populares',
            madrid_desc: 'Capital vibrante con conexiones a todo el país.',
            barcelona_desc: 'Rutas rápidas y vistas espectaculares al Mediterráneo.',
            sevilla_desc: 'Cultura, historia y gastronomía en cada estación.',
            valencia_desc: 'Costa mediterránea y ciudades modernas conectadas por tren.',
            popular_button: 'Ver rutas',
            offers_title: 'Abonos y Promociones',
            buy_button: 'Comprar',
            footer_services: 'Servicios',
            footer_legal: 'Información legal',
            footer_social: 'Redes sociales',
            footer_billetes: 'Billetes',
            footer_horarios: 'Horarios',
            footer_ofertas: 'Ofertas',
            footer_atencion: 'Atención al cliente',
            footer_aviso: 'Aviso legal',
            footer_privacidad: 'Privacidad',
            footer_cookies: 'Cookies',
            footer_terminos: 'Términos y condiciones'
        },
        en: {
            page_title: 'TrainWeb - Train Tickets',
            nav_billetes: 'Tickets',
            nav_ofertas: 'Deals',
            nav_ayuda: 'Help',
            nav_idiomas: 'Languages',
            lang_es: 'Spanish',
            lang_en: 'English',
            lang_fr: 'French',
            lang_de: 'German',
            login_button: 'Sign in',
            hero_title: 'Find your train',
            trip_oneway: 'One way',
            trip_roundtrip: 'Round trip',
            origin_placeholder: 'Origin',
            destination_placeholder: 'Destination',
            passengers_1: '1 passenger',
            passengers_2: '2 passengers',
            passengers_3: '3 passengers',
            passengers_4: '4 passengers',
            search_button: 'Search tickets',
            popular_title: 'Popular destinations',
            madrid_desc: 'A vibrant capital with connections across the country.',
            barcelona_desc: 'Fast routes and spectacular Mediterranean views.',
            sevilla_desc: 'Culture, history and gastronomy at every station.',
            valencia_desc: 'Mediterranean coast and modern cities connected by rail.',
            popular_button: 'View routes',
            offers_title: 'Passes and promotions',
            buy_button: 'Buy',
            footer_services: 'Services',
            footer_legal: 'Legal information',
            footer_social: 'Social media',
            footer_billetes: 'Tickets',
            footer_horarios: 'Timetables',
            footer_ofertas: 'Deals',
            footer_atencion: 'Customer support',
            footer_aviso: 'Legal notice',
            footer_privacidad: 'Privacy',
            footer_cookies: 'Cookies',
            footer_terminos: 'Terms and conditions'
        },
        fr: {
            page_title: 'TrainWeb - Billets de train',
            nav_billetes: 'Billets',
            nav_ofertas: 'Offres',
            nav_ayuda: 'Aide',
            nav_idiomas: 'Langues',
            lang_es: 'Espagnol',
            lang_en: 'Anglais',
            lang_fr: 'Français',
            lang_de: 'Allemand',
            login_button: 'Se connecter',
            hero_title: 'Trouvez votre train',
            trip_oneway: 'Aller simple',
            trip_roundtrip: 'Aller-retour',
            origin_placeholder: 'Origine',
            destination_placeholder: 'Destination',
            passengers_1: '1 passager',
            passengers_2: '2 passagers',
            passengers_3: '3 passagers',
            passengers_4: '4 passagers',
            search_button: 'Rechercher des billets',
            popular_title: 'Destinations populaires',
            madrid_desc: 'Capitale dynamique avec des connexions dans tout le pays.',
            barcelona_desc: 'Trajets rapides et vues spectaculaires sur la Méditerranée.',
            sevilla_desc: 'Culture, histoire et gastronomie à chaque station.',
            valencia_desc: 'Côte méditerranéenne et villes modernes reliées par le train.',
            popular_button: 'Voir les trajets',
            offers_title: 'Abonnements et promotions',
            buy_button: 'Acheter',
            footer_services: 'Services',
            footer_legal: 'Informations légales',
            footer_social: 'Réseaux sociaux',
            footer_billetes: 'Billets',
            footer_horarios: 'Horaires',
            footer_ofertas: 'Offres',
            footer_atencion: 'Service client',
            footer_aviso: 'Mentions légales',
            footer_privacidad: 'Confidentialité',
            footer_cookies: 'Cookies',
            footer_terminos: 'Conditions générales'
        },
        de: {
            page_title: 'TrainWeb - Zugtickets',
            nav_billetes: 'Tickets',
            nav_ofertas: 'Angebote',
            nav_ayuda: 'Hilfe',
            nav_idiomas: 'Sprachen',
            lang_es: 'Spanisch',
            lang_en: 'Englisch',
            lang_fr: 'Französisch',
            lang_de: 'Deutsch',
            login_button: 'Anmelden',
            hero_title: 'Finden Sie Ihren Zug',
            trip_oneway: 'Nur Hinfahrt',
            trip_roundtrip: 'Hin- und Rückfahrt',
            origin_placeholder: 'Abfahrt',
            destination_placeholder: 'Ziel',
            passengers_1: '1 Fahrgast',
            passengers_2: '2 Fahrgäste',
            passengers_3: '3 Fahrgäste',
            passengers_4: '4 Fahrgäste',
            search_button: 'Tickets suchen',
            popular_title: 'Beliebte Reiseziele',
            madrid_desc: 'Lebendige Hauptstadt mit Verbindungen im ganzen Land.',
            barcelona_desc: 'Schnelle Strecken und spektakuläre Mittelmeerblicke.',
            sevilla_desc: 'Kultur, Geschichte und Gastronomie an jeder Station.',
            valencia_desc: 'Mittelmeerküste und moderne Städte per Zug verbunden.',
            popular_button: 'Routen ansehen',
            offers_title: 'Abos und Aktionen',
            buy_button: 'Kaufen',
            footer_services: 'Services',
            footer_legal: 'Rechtliche Informationen',
            footer_social: 'Soziale Netzwerke',
            footer_billetes: 'Tickets',
            footer_horarios: 'Fahrpläne',
            footer_ofertas: 'Angebote',
            footer_atencion: 'Kundendienst',
            footer_aviso: 'Impressum',
            footer_privacidad: 'Datenschutz',
            footer_cookies: 'Cookies',
            footer_terminos: 'Allgemeine Geschäftsbedingungen'
        }
    };

    const storageKey = 'trainweb-lang';
    const languageLinks = document.querySelectorAll('[data-lang]');
    const userActions = document.getElementById('userActions');
    const trackedRoot = document.body;

    function getLanguage() {
        return localStorage.getItem(storageKey) || 'es';
    }

    function t(key, lang = getLanguage()) {
        return (translations[lang] && translations[lang][key]) || translations.es[key] || key;
    }

    function setText(selector, key) {
        const el = document.querySelector(selector);
        if (el) {
            el.textContent = t(key);
        }
    }

    function applyTranslations() {
        const lang = getLanguage();
        document.documentElement.lang = lang;
        document.title = t('page_title', lang);

        const translationsMap = [
            ['[data-i18n="nav-billetes"]', 'nav_billetes'],
            ['[data-i18n="nav-ofertas"]', 'nav_ofertas'],
            ['[data-i18n="nav-ayuda"]', 'nav_ayuda'],
            ['[data-i18n="nav-idiomas"]', 'nav_idiomas'],
            ['[data-i18n="lang-es"]', 'lang_es'],
            ['[data-i18n="lang-en"]', 'lang_en'],
            ['[data-i18n="lang-fr"]', 'lang_fr'],
            ['[data-i18n="lang-de"]', 'lang_de'],
            ['[data-i18n="login-button"]', 'login_button'],
            ['[data-i18n="hero-title"]', 'hero_title'],
            ['[data-i18n="trip-oneway"]', 'trip_oneway'],
            ['[data-i18n="trip-roundtrip"]', 'trip_roundtrip'],
            ['[data-i18n="search-button"]', 'search_button'],
            ['[data-i18n="popular-title"]', 'popular_title'],
            ['[data-i18n="popular-madrid-desc"]', 'madrid_desc'],
            ['[data-i18n="popular-barcelona-desc"]', 'barcelona_desc'],
            ['[data-i18n="popular-sevilla-desc"]', 'sevilla_desc'],
            ['[data-i18n="popular-valencia-desc"]', 'valencia_desc'],
            ['[data-i18n="popular-button"]', 'popular_button'],
            ['[data-i18n="offers-title"]', 'offers_title'],
            ['[data-i18n="buy-button"]', 'buy_button'],
            ['[data-i18n="footer-services"]', 'footer_services'],
            ['[data-i18n="footer-legal"]', 'footer_legal'],
            ['[data-i18n="footer-social"]', 'footer_social'],
            ['[data-i18n="footer-billetes"]', 'footer_billetes'],
            ['[data-i18n="footer-horarios"]', 'footer_horarios'],
            ['[data-i18n="footer-ofertas"]', 'footer_ofertas'],
            ['[data-i18n="footer-atencion"]', 'footer_atencion'],
            ['[data-i18n="footer-aviso"]', 'footer_aviso'],
            ['[data-i18n="footer-privacidad"]', 'footer_privacidad'],
            ['[data-i18n="footer-cookies"]', 'footer_cookies'],
            ['[data-i18n="footer-terminos"]', 'footer_terminos']
        ];

        translationsMap.forEach(([selector, key]) => setText(selector, key));

        const origin = document.getElementById('origen');
        const destination = document.getElementById('destino');
        if (origin) origin.placeholder = t('origin_placeholder', lang);
        if (destination) destination.placeholder = t('destination_placeholder', lang);

        const passengerOptions = document.querySelectorAll('select[name="pasajeros"] option');
        const passengerKeys = ['passengers_1', 'passengers_2', 'passengers_3', 'passengers_4'];
        passengerOptions.forEach((option, index) => {
            if (passengerKeys[index]) {
                option.textContent = t(passengerKeys[index], lang);
            }
        });

        document.querySelectorAll('.btn-popular').forEach((button) => {
            button.textContent = t('popular_button', lang);
        });

        document.querySelectorAll('.offer-card a.btn-popular').forEach((button) => {
            button.textContent = t('buy_button', lang);
        });

        const loginButton = document.querySelector('.btn-login');
        if (loginButton) {
            const icon = loginButton.querySelector('i');
            loginButton.innerHTML = '';
            if (icon) {
                loginButton.appendChild(icon.cloneNode(true));
                loginButton.append(' ' + t('login_button', lang));
            } else {
                loginButton.textContent = t('login_button', lang);
            }
        }

        document.querySelectorAll('.dropdown-content a').forEach((link, index) => {
            const keys = ['lang_es', 'lang_en', 'lang_fr', 'lang_de'];
            if (keys[index]) {
                link.textContent = t(keys[index], lang);
            }
        });

        const accountMenuLinks = document.querySelectorAll('.account-menu a');
        if (accountMenuLinks.length >= 2) {
            accountMenuLinks[0].innerHTML = `<i class="fa-solid fa-user"></i> ${lang === 'en' ? 'My profile' : lang === 'fr' ? 'Mon profil' : lang === 'de' ? 'Mein Profil' : 'Mi perfil'}`;
            accountMenuLinks[1].innerHTML = `<i class="fa-solid fa-right-from-bracket"></i> ${lang === 'en' ? 'Sign out' : lang === 'fr' ? 'Se déconnecter' : lang === 'de' ? 'Abmelden' : 'Cerrar sesión'}`;
        }
    }

    function setLanguage(lang) {
        if (!translations[lang]) {
            lang = 'es';
        }
        localStorage.setItem(storageKey, lang);
        applyTranslations();
    }

    languageLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            setLanguage(link.dataset.lang);
        });
    });

    if (userActions && 'MutationObserver' in window) {
        const observer = new MutationObserver(() => {
            applyTranslations();
        });
        observer.observe(userActions, { childList: true, subtree: true });
    }

    window.trainwebI18n = { t, getLanguage, setLanguage, applyTranslations };
    applyTranslations();
});
