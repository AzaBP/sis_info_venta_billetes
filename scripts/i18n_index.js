document.addEventListener('DOMContentLoaded', () => {
    const translations = {
        es: {
            billetes: 'Billetes',
            idiomas: 'Idiomas',
            es: 'Español',
            en: 'Inglés',
            fr: 'Francés',
            de: 'Alemán',
            ofertas: 'Ofertas',
            ayuda: 'Ayuda',
            iniciar_sesion: 'Iniciar sesión',
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
            abonos_promociones: 'Abonos y Promociones',
            comprar: 'Comprar'
        },
        en: {
            billetes: 'Tickets',
            idiomas: 'Languages',
            es: 'Spanish',
            en: 'English',
            fr: 'French',
            de: 'German',
            ofertas: 'Deals',
            ayuda: 'Help',
            iniciar_sesion: 'Sign in',
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
            abonos_promociones: 'Passes and promotions',
            comprar: 'Buy'
        },
        fr: {
            billetes: 'Billets',
            idiomas: 'Langues',
            es: 'Espagnol',
            en: 'Anglais',
            fr: 'Français',
            de: 'Allemand',
            ofertas: 'Offres',
            ayuda: 'Aide',
            iniciar_sesion: 'Se connecter',
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
            abonos_promociones: 'Abonnements et promotions',
            comprar: 'Acheter'
        },
        de: {
            billetes: 'Tickets',
            idiomas: 'Sprachen',
            es: 'Spanisch',
            en: 'Englisch',
            fr: 'Französisch',
            de: 'Deutsch',
            ofertas: 'Angebote',
            ayuda: 'Hilfe',
            iniciar_sesion: 'Anmelden',
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
            abonos_promociones: 'Abos und Aktionen',
            comprar: 'Kaufen'
        }
    };

    const storageKey = 'trainweb-lang';
    const languageLinks = document.querySelectorAll('[data-lang]');
    const userActions = document.getElementById('userActions');

    function getLanguage() {
        return localStorage.getItem(storageKey) || 'es';
    }

    function t(key, lang = getLanguage()) {
        return (translations[lang] && translations[lang][key]) || translations.es[key] || key;
    }

    function applyTranslations() {
        const lang = getLanguage();
        document.documentElement.lang = lang;
        document.title = t('busca_tren') + ' - TrainWeb';

        // Batch process all [data-i18n] elements
        document.querySelectorAll('[data-i18n]').forEach((el) => {
            const key = el.getAttribute('data-i18n');
            const text = t(key, lang);
            if (el.tagName === 'SELECT' || el.tagName === 'TEXTAREA' || el.tagName === 'INPUT') {
                el.placeholder = text;
            } else {
                el.textContent = text;
            }
        });

        // Update placeholder para inputs de origen/destino
        const origin = document.getElementById('origen');
        const destination = document.getElementById('destino');
        if (origin) origin.placeholder = lang === 'en' ? 'Origin' : lang === 'fr' ? 'Origine' : lang === 'de' ? 'Abfahrt' : 'Origen';
        if (destination) destination.placeholder = lang === 'en' ? 'Destination' : lang === 'fr' ? 'Destination' : lang === 'de' ? 'Ziel' : 'Destino';
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

    // Debounced MutationObserver
    let translationTimeout = null;
    if (userActions && 'MutationObserver' in window) {
        const observer = new MutationObserver(() => {
            clearTimeout(translationTimeout);
            translationTimeout = setTimeout(() => {
                applyTranslations();
            }, 100);
        });
        observer.observe(userActions, { childList: true });
    }

    window.trainwebI18n = { t, getLanguage, setLanguage, applyTranslations };
    applyTranslations();
});
