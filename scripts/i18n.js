document.addEventListener('DOMContentLoaded', () => {
    const translations = {
        es: {
            // Navegación principal
            billetes: 'Billetes',
            idiomas: 'Idiomas',
            es: 'Español',
            en: 'Inglés',
            fr: 'Francés',
            de: 'Alemán',
            ofertas: 'Ofertas',
            ayuda: 'Ayuda',
            inicio: 'Inicio',
            iniciar_sesion: 'Iniciar sesión',
            cerrar_sesion: 'Cerrar sesión',
            mi_perfil: 'Mi perfil',
            
            // Index / Búsqueda
            busca_tren: 'Busca tu tren',
            solo_ida: 'Solo ida',
            ida_vuelta: 'Ida y vuelta',
            pasajero_1: '1 pasajero',
            pasajero_2: '2 pasajeros',
            pasajero_3: '3 pasajeros',
            pasajero_4: '4 pasajeros',
            buscar_billetes: 'Buscar billetes',
            destinos_populares: 'Destinos Populares',
            desc_madrid: 'Capital vibrante con conexiones a todo el país.',
            desc_barcelona: 'Rutas rápidas y vistas espectaculares al Mediterráneo.',
            desc_sevilla: 'Cultura, historia y gastronomía en cada estación.',
            desc_valencia: 'Costa mediterránea y ciudades modernas conectadas por tren.',
            ver_rutas: 'Ver rutas',
            
            // Abonos y Promociones
            abonos_promociones: 'Abonos y Promociones',
            comprar: 'Comprar',
            usa_codigo: 'Usa el código',
            valido_hasta: 'Válido hasta',
            sin_ofertas: 'Actualmente no hay ofertas disponibles. ¡Vuelve pronto!',
            dto: 'Dto.',
            
            // Billetes
            mis_billetes: 'Mis Billetes',
            fecha: 'Fecha',
            hora_salida: 'Hora Salida',
            hora_llegada: 'Hora Llegada',
            asiento: 'Asiento',
            precio: 'Precio',
            origen: 'Origen',
            destino: 'Destino',
            
            // Compra
            resumen_compra: 'Resumen de Compra',
            pasajeros: 'Pasajeros',
            total: 'Total',
            confirmar_compra: 'Confirmar Compra',
            
            // Ayuda
            ayuda_titulo: '¿En qué podemos ayudarte?',
            ayuda_desc: 'Busca soluciones rápidas a tus dudas sobre viajes, billetes y servicios.',
            temas_frecuentes: 'Temas frecuentes',
            
            // Footer
            footer_services: 'Servicios',
            footer_descripcion: 'Plataforma digital para la búsqueda y compra de billetes de tren en todo el territorio nacional.',
            footer_legal: 'Información legal',
            footer_social: 'Redes sociales',
            footer_billetes: 'Billetes',
            footer_horarios: 'Horarios',
            footer_ofertas: 'Ofertas',
            footer_atencion: 'Atención al cliente',
            footer_aviso: 'Aviso legal',
            footer_privacidad: 'Privacidad',
            footer_cookies: 'Cookies',
            footer_terminos: 'Términos y condiciones',
            footer_copyright: '© 2026 TrainWeb · Todos los derechos reservados'
        },
        en: {
            // Navigation
            billetes: 'Tickets',
            idiomas: 'Languages',
            es: 'Spanish',
            en: 'English',
            fr: 'French',
            de: 'German',
            ofertas: 'Deals',
            ayuda: 'Help',
            inicio: 'Home',
            iniciar_sesion: 'Sign in',
            cerrar_sesion: 'Sign out',
            mi_perfil: 'My profile',
            
            // Index / Search
            busca_tren: 'Find your train',
            solo_ida: 'One way',
            ida_vuelta: 'Round trip',
            pasajero_1: '1 passenger',
            pasajero_2: '2 passengers',
            pasajero_3: '3 passengers',
            pasajero_4: '4 passengers',
            buscar_billetes: 'Search tickets',
            destinos_populares: 'Popular destinations',
            desc_madrid: 'A vibrant capital with connections across the country.',
            desc_barcelona: 'Fast routes and spectacular Mediterranean views.',
            desc_sevilla: 'Culture, history and gastronomy at every station.',
            desc_valencia: 'Mediterranean coast and modern cities connected by rail.',
            ver_rutas: 'View routes',
            
            // Passes and Promotions
            abonos_promociones: 'Passes and promotions',
            comprar: 'Buy',
            usa_codigo: 'Use code',
            valido_hasta: 'Valid until',
            sin_ofertas: 'There are currently no offers available. Please come back soon!',
            dto: 'Off.',
            
            // Tickets
            mis_billetes: 'My Tickets',
            fecha: 'Date',
            hora_salida: 'Departure',
            hora_llegada: 'Arrival',
            asiento: 'Seat',
            precio: 'Price',
            origen: 'Origin',
            destino: 'Destination',
            
            // Purchase
            resumen_compra: 'Purchase Summary',
            pasajeros: 'Passengers',
            total: 'Total',
            confirmar_compra: 'Confirm Purchase',
            
            // Help
            ayuda_titulo: 'How can we help you?',
            ayuda_desc: 'Find quick solutions to your questions about trips, tickets and services.',
            temas_frecuentes: 'Frequent topics',
            
            // Footer
            footer_services: 'Services',
            footer_descripcion: 'Digital platform for searching and buying train tickets across the country.',
            footer_legal: 'Legal information',
            footer_social: 'Social media',
            footer_billetes: 'Tickets',
            footer_horarios: 'Timetables',
            footer_ofertas: 'Deals',
            footer_atencion: 'Customer support',
            footer_aviso: 'Legal notice',
            footer_privacidad: 'Privacy',
            footer_cookies: 'Cookies',
            footer_terminos: 'Terms and conditions',
            footer_copyright: '© 2026 TrainWeb · All rights reserved'
        },
        fr: {
            // Navigation
            billetes: 'Billets',
            idiomas: 'Langues',
            es: 'Espagnol',
            en: 'Anglais',
            fr: 'Français',
            de: 'Allemand',
            ofertas: 'Offres',
            ayuda: 'Aide',
            inicio: 'Accueil',
            iniciar_sesion: 'Se connecter',
            cerrar_sesion: 'Se déconnecter',
            mi_perfil: 'Mon profil',
            
            // Index / Search
            busca_tren: 'Trouvez votre train',
            solo_ida: 'Aller simple',
            ida_vuelta: 'Aller-retour',
            pasajero_1: '1 passager',
            pasajero_2: '2 passagers',
            pasajero_3: '3 passagers',
            pasajero_4: '4 passagers',
            buscar_billetes: 'Rechercher des billets',
            destinos_populares: 'Destinations populaires',
            desc_madrid: 'Capitale dynamique avec des connexions dans tout le pays.',
            desc_barcelona: 'Trajets rapides et vues spectaculaires sur la Méditerranée.',
            desc_sevilla: 'Culture, histoire et gastronomie à chaque station.',
            desc_valencia: 'Côte méditerranéenne et villes modernes reliées par le train.',
            ver_rutas: 'Voir les trajets',
            
            // Passes and Promotions
            abonos_promociones: 'Abonnements et promotions',
            comprar: 'Acheter',
            usa_codigo: 'Utilisez le code',
            valido_hasta: 'Valable jusqu\'au',
            sin_ofertas: 'Il n\'y a actuellement aucune offre disponible. Revenez bientôt!',
            dto: 'Réduc.',
            
            // Tickets
            mis_billetes: 'Mes Billets',
            fecha: 'Date',
            hora_salida: 'Départ',
            hora_llegada: 'Arrivée',
            asiento: 'Siège',
            precio: 'Prix',
            origen: 'Origine',
            destino: 'Destination',
            
            // Purchase
            resumen_compra: 'Résumé de l\'achat',
            pasajeros: 'Passagers',
            total: 'Total',
            confirmar_compra: 'Confirmer l\'achat',
            
            // Help
            ayuda_titulo: 'Comment pouvons-nous vous aider?',
            ayuda_desc: 'Trouvez des solutions rapides à vos questions sur les voyages, les billets et les services.',
            temas_frecuentes: 'Sujets fréquents',
            
            // Footer
            footer_services: 'Services',
            footer_descripcion: 'Plateforme numérique pour la recherche et l\'achat de billets de train sur tout le territoire national.',
            footer_legal: 'Informations légales',
            footer_social: 'Réseaux sociaux',
            footer_billetes: 'Billets',
            footer_horarios: 'Horaires',
            footer_ofertas: 'Offres',
            footer_atencion: 'Service client',
            footer_aviso: 'Mentions légales',
            footer_privacidad: 'Confidentialité',
            footer_cookies: 'Cookies',
            footer_terminos: 'Conditions générales',
            footer_copyright: '© 2026 TrainWeb · Tous droits réservés'
        },
        de: {
            // Navigation
            billetes: 'Tickets',
            idiomas: 'Sprachen',
            es: 'Spanisch',
            en: 'Englisch',
            fr: 'Französisch',
            de: 'Deutsch',
            ofertas: 'Angebote',
            ayuda: 'Hilfe',
            inicio: 'Startseite',
            iniciar_sesion: 'Anmelden',
            cerrar_sesion: 'Abmelden',
            mi_perfil: 'Mein Profil',
            
            // Index / Search
            busca_tren: 'Finden Sie Ihren Zug',
            solo_ida: 'Nur Hinfahrt',
            ida_vuelta: 'Hin- und Rückfahrt',
            pasajero_1: '1 Fahrgast',
            pasajero_2: '2 Fahrgäste',
            pasajero_3: '3 Fahrgäste',
            pasajero_4: '4 Fahrgäste',
            buscar_billetes: 'Tickets suchen',
            destinos_populares: 'Beliebte Reiseziele',
            desc_madrid: 'Lebendige Hauptstadt mit Verbindungen im ganzen Land.',
            desc_barcelona: 'Schnelle Strecken und spektakuläre Mittelmeerblicke.',
            desc_sevilla: 'Kultur, Geschichte und Gastronomie an jeder Station.',
            desc_valencia: 'Mittelmeerküste und moderne Städte per Zug verbunden.',
            ver_rutas: 'Routen ansehen',
            
            // Passes and Promotions
            abonos_promociones: 'Abos und Aktionen',
            comprar: 'Kaufen',
            usa_codigo: 'Code verwenden',
            valido_hasta: 'Gültig bis',
            sin_ofertas: 'Derzeit sind keine Angebote verfügbar. Bitte kommen Sie bald wieder!',
            dto: 'Rab.',
            
            // Tickets
            mis_billetes: 'Meine Tickets',
            fecha: 'Datum',
            hora_salida: 'Abfahrt',
            hora_llegada: 'Ankunft',
            asiento: 'Platz',
            precio: 'Preis',
            origen: 'Abfahrt',
            destino: 'Ziel',
            
            // Purchase
            resumen_compra: 'Kaufzusammenfassung',
            pasajeros: 'Fahrgäste',
            total: 'Gesamt',
            confirmar_compra: 'Kauf bestätigen',
            
            // Help
            ayuda_titulo: 'Wie können wir dir helfen?',
            ayuda_desc: 'Finden Sie schnelle Lösungen für Ihre Fragen zu Reisen, Tickets und Diensten.',
            temas_frecuentes: 'Häufige Themen',
            
            // Footer
            footer_services: 'Services',
            footer_descripcion: 'Digitale Plattform für die Suche und den Kauf von Zugtickets im ganzen Land.',
            footer_legal: 'Rechtliche Informationen',
            footer_social: 'Soziale Netzwerke',
            footer_billetes: 'Tickets',
            footer_horarios: 'Fahrpläne',
            footer_ofertas: 'Angebote',
            footer_atencion: 'Kundendienst',
            footer_aviso: 'Impressum',
            footer_privacidad: 'Datenschutz',
            footer_cookies: 'Cookies',
            footer_terminos: 'Allgemeine Geschäftsbedingungen',
            footer_copyright: '© 2026 TrainWeb · Alle Rechte vorbehalten'
        }
    };

    const storageKey = 'trainweb-lang';
    const supportedLanguages = ['es', 'en', 'fr', 'de'];

    function normalizeLanguage(lang) {
        return supportedLanguages.includes(lang) ? lang : 'es';
    }

    function getLanguage() {
        const saved = localStorage.getItem(storageKey);
        return normalizeLanguage(saved || 'es');
    }

    function t(key, lang = getLanguage()) {
        const normalizedKey = String(key || '').replace(/-/g, '_');
        return (
            (translations[lang] && (translations[lang][key] || translations[lang][normalizedKey])) ||
            (translations.es && (translations.es[key] || translations.es[normalizedKey])) ||
            null
        );
    }

    function applyTranslations() {
        const lang = getLanguage();
        document.documentElement.lang = lang;

        // Procesar todos los elementos con [data-i18n]
        document.querySelectorAll('[data-i18n]').forEach((el) => {
            const key = el.getAttribute('data-i18n');
            const text = t(key, lang);
            if (text === null) return;
            if (el.tagName === 'SELECT' || el.tagName === 'TEXTAREA' || el.tagName === 'INPUT') {
                el.placeholder = text;
            } else {
                el.textContent = text;
            }
        });

        // Actualizar placeholders para inputs de búsqueda
        const origin = document.getElementById('origen');
        const destination = document.getElementById('destino');
        if (origin) origin.placeholder = lang === 'en' ? 'Origin' : lang === 'fr' ? 'Origine' : lang === 'de' ? 'Abfahrt' : 'Origen';
        if (destination) destination.placeholder = lang === 'en' ? 'Destination' : lang === 'fr' ? 'Destination' : lang === 'de' ? 'Ziel' : 'Destino';
    }

    function setLanguage(lang) {
        const safeLang = normalizeLanguage(lang);
        const currentLang = getLanguage();
        localStorage.setItem(storageKey, safeLang);
        if (currentLang !== safeLang) {
            window.location.reload();
            return;
        }
        applyTranslations();
    }

    // Event listeners para cambio de idioma
    const languageLinks = document.querySelectorAll('[data-lang]');
    languageLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const lang = (link.dataset.lang || '').toLowerCase();
            setLanguage(lang);
        });
    });

    // Fallback para enlaces dinámicos
    document.addEventListener('click', (event) => {
        const langLink = event.target.closest('[data-lang]');
        if (!langLink) return;
        event.preventDefault();
        event.stopPropagation();
        const lang = (langLink.dataset.lang || '').toLowerCase();
        setLanguage(lang);
    });

    // Inicializar
    const startupLanguage = getLanguage();
    localStorage.setItem(storageKey, startupLanguage);
    window.trainwebI18n = { t, getLanguage, setLanguage, applyTranslations };
    applyTranslations();
});
