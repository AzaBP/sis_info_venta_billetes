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
            abonos_promociones: 'Abonos',
            comprar: 'Comprar',
            usa_codigo: 'Usa el código',
            valido_hasta: 'Válido hasta',
            sin_ofertas: 'Actualmente no hay ofertas disponibles. ¡Vuelve pronto!',
            dto: 'Dto.',
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
            footer_copyright: '© 2026 TrainWeb · Todos los derechos reservados',
            // Cancelación
            cancelar_titulo: '¿Necesitas cancelar tu billete?',
            cancelar_desc: 'Introduce el código localizador de tu billete para proceder con la cancelación automática.',
            cancelar_placeholder: 'Ej: TW-2024...',
            boton_cancelar: 'Cancelar viaje',
            // Ofertas Especiales (Popups)
            oferta_especial_titulo: '¡Oferta Especial! 🚅',
            oferta_especial_body: 'Usa el código <strong>{code}</strong> y obtén un <strong>{pct}%</strong> de descuento.',
            ver_mas_ofertas: 'Ver más en ofertas'
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
            abonos_promociones: 'Passes',
            comprar: 'Buy',
            usa_codigo: 'Use code',
            valido_hasta: 'Valid until',
            sin_ofertas: 'There are currently no offers available. Please come back soon!',
            dto: 'Off.',
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
            footer_copyright: '© 2026 TrainWeb · All rights reserved',
            // Cancelación
            cancelar_titulo: 'Need to cancel your ticket?',
            cancelar_desc: 'Enter your ticket locator code to proceed with the automatic cancellation.',
            cancelar_placeholder: 'Ex: TW-2024...',
            boton_cancelar: 'Cancel trip',
            // Ofertas Especiales (Popups)
            oferta_especial_titulo: 'Special Offer! 🚅',
            oferta_especial_body: 'Use the code <strong>{code}</strong> and get a <strong>{pct}%</strong> discount.',
            ver_mas_ofertas: 'See more deals'
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
            abonos_promociones: 'Abonnements',
            comprar: 'Acheter',
            usa_codigo: 'Utilisez le code',
            valido_hasta: 'Valable jusqu\'au',
            sin_ofertas: 'Il n\'y a actuellement aucune offre disponible. Revenez bientôt!',
            dto: 'Réduc.',
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
            footer_copyright: '© 2026 TrainWeb · Tous droits réservés',
            // Cancelación
            cancelar_titulo: 'Besoin d\'annuler votre billet ?',
            cancelar_desc: 'Entrez le code de localisation de votre billet pour procéder à l\'annulation automatique.',
            cancelar_placeholder: 'Ex: TW-2024...',
            boton_cancelar: 'Annuler le voyage',
            // Ofertas Especiales (Popups)
            oferta_especial_titulo: 'Offre Spéciale ! 🚅',
            oferta_especial_body: 'Utilisez le code <strong>{code}</strong> et profitez de <strong>{pct}%</strong> de réduction.',
            ver_mas_ofertas: 'Voir plus d\'offres'
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
            abonos_promociones: 'Abos',
            comprar: 'Kaufen',
            usa_codigo: 'Code verwenden',
            valido_hasta: 'Gültig bis',
            sin_ofertas: 'Derzeit sind keine Angebote verfügbar. Bitte kommen Sie bald wieder!',
            dto: 'Rab.',
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
            footer_copyright: '© 2026 TrainWeb · Alle Rechte vorbehalten',
            // Cancelación
            cancelar_titulo: 'Müssen Sie Ihr Ticket stornieren?',
            cancelar_desc: 'Geben Sie Ihren Buchungscode ein, um mit der automatischen Stornierung fortzufahren.',
            cancelar_placeholder: 'Z. B.: TW-2024...',
            boton_cancelar: 'Reise stornieren',
            // Ofertas Especiales (Popups)
            oferta_especial_titulo: 'Sonderangebot! 🚅',
            oferta_especial_body: 'Nutzen Sie den Code <strong>{code}</strong> und erhalten Sie <strong>{pct}%</strong> Rabatt.',
            ver_mas_ofertas: 'Mehr Angebote sehen'
        }
    };

    const abonoTranslations = {
        en: {
            'Estudiantes': 'Students',
            'Abono para estudiantes que necesitan desplazarse para realizar sus estudios': 'Pass for students who need to travel for their studies',
            'abono descuento': 'Discount pass',
            'abono que va durisimo y mola tajo': 'Very powerful discount pass',
            'Abonico Majico': 'Magic pass',
            'abono para gente tajo maja': 'Pass for very nice people',
            'Abono 60': 'Pass 60',
            'Abono para jovenes de 60 años, que les guste viajar a un precio asequible': 'Pass for young 60-year-old travelers who want affordable prices',
            'Mensual': 'Monthly',
            'Trimestral': 'Quarterly',
            'Anual': 'Annual',
            '10 viajes': '10 trips',
            '20 viajes': '20 trips'
        },
        fr: {
            'Estudiantes': 'Étudiants',
            'Abono para estudiantes que necesitan desplazarse para realizar sus estudios': 'Abonnement pour les étudiants qui doivent se déplacer pour leurs études',
            'abono descuento': 'Abonnement réduction',
            'abono que va durisimo y mola tajo': 'Abonnement super puissant',
            'Abonico Majico': 'Abonnement magique',
            'abono para gente tajo maja': 'Abonnement pour les gens très sympas',
            'Abono 60': 'Abonnement 60',
            'Abono para jovenes de 60 años, que les guste viajar a un precio asequible': 'Abonnement pour les jeunes de 60 ans qui aiment voyager à prix abordable'
        },
        de: {
            'Estudiantes': 'Studenten',
            'Abono para estudiantes que necesitan desplazarse para realizar sus estudios': 'Pass für Studierende, die für ihr Studium reisen müssen',
            'abono descuento': 'Rabattpass',
            'abono que va durisimo y mola tajo': 'Sehr starker Rabattpass',
            'Abonico Majico': 'Magischer Pass',
            'abono para gente tajo maja': 'Pass für sehr nette Leute',
            'Abono 60': 'Pass 60',
            'Abono para jovenes de 60 años, que les guste viajar a un precio asequible': 'Pass für junge 60-Jährige, die günstig reisen möchten'
        }
    };

    const storageKey = 'trainweb-lang';
    const supportedLanguages = ['es', 'en', 'fr', 'de'];
    const languageLinks = document.querySelectorAll('[data-lang]');
    const userActions = document.getElementById('userActions');

    function normalizeLanguage(lang) {
        return supportedLanguages.includes(lang) ? lang : 'es';
    }

    function getLanguage() {
        const saved = localStorage.getItem(storageKey);
        return normalizeLanguage(saved || 'es');
    }

    function t(key, params = {}, lang = getLanguage()) {
        const normalizedKey = String(key || '').replace(/-/g, '_');
        let text = (
            (translations[lang] && (translations[lang][key] || translations[lang][normalizedKey])) ||
            (translations.es && (translations.es[key] || translations.es[normalizedKey])) ||
            null
        );

        if (text && typeof text === 'string') {
            Object.keys(params).forEach(param => {
                text = text.replace(new RegExp(`{${param}}`, 'g'), params[param]);
            });
        }
        return text;
    }

    function applyTranslations() {
        const lang = getLanguage();
        document.documentElement.lang = lang;
        document.title = t('busca_tren') + ' - TrainWeb';

        // Batch process all [data-i18n] elements
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

        // Update placeholder para inputs de origen/destino
        const origin = document.getElementById('origen');
        const destination = document.getElementById('destino');
        if (origin) origin.placeholder = lang === 'en' ? 'Origin' : lang === 'fr' ? 'Origine' : lang === 'de' ? 'Abfahrt' : 'Origen';
        if (destination) destination.placeholder = lang === 'en' ? 'Destination' : lang === 'fr' ? 'Destination' : lang === 'de' ? 'Ziel' : 'Destino';

        // Translate dynamic abono names/descriptions without changing DB values
        document.querySelectorAll('a[href^="comprar_abono.php"]').forEach((buyLink) => {
            const card = buyLink.closest('.offer-card');
            if (!card) return;

            const title = card.querySelector('h3');
            const desc = card.querySelector('p');

            if (title) {
                if (!title.dataset.original) {
                    title.dataset.original = title.textContent.trim();
                }
                const originalTitle = title.dataset.original;
                title.textContent = (abonoTranslations[lang] && abonoTranslations[lang][originalTitle]) || originalTitle;
            }

            if (desc) {
                if (!desc.dataset.original) {
                    desc.dataset.original = desc.textContent.trim();
                }
                const originalDesc = desc.dataset.original;
                desc.textContent = (abonoTranslations[lang] && abonoTranslations[lang][originalDesc]) || originalDesc;
            }
        });

        // Translate promotion discount suffix by language
        document.querySelectorAll('.promo-discount').forEach((discountEl) => {
            const rawValue = discountEl.dataset.discount || '0';
            discountEl.textContent = `-${rawValue}% ${t('dto', lang)}`;
        });
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

    languageLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const lang = (link.dataset.lang || '').toLowerCase();
            setLanguage(lang);
        });
    });

    // Fallback for dynamically rendered language links.
    document.addEventListener('click', (event) => {
        const langLink = event.target.closest('[data-lang]');
        if (!langLink) return;
        event.preventDefault();
        event.stopPropagation();
        const lang = (langLink.dataset.lang || '').toLowerCase();
        setLanguage(lang);
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

    // Force a valid default language on first load.
    const startupLanguage = getLanguage();
    localStorage.setItem(storageKey, startupLanguage);

    window.trainwebI18n = { t, getLanguage, setLanguage, applyTranslations };
    applyTranslations();
});
