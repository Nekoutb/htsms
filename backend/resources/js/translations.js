const french = {
    'Welcome back': 'Bon retour',
    'Sign in to HTSMS': 'Se connecter à EA HTSMS',
    'Use your verified business account.': 'Utilisez votre compte professionnel vérifié.',
    'Email address': 'Adresse e-mail',
    'Password': 'Mot de passe',
    'Sign in': 'Se connecter',
    'Create an account': 'Créer un compte',
    'Start building': 'Commencer',
    'Create your account': 'Créez votre compte',
    'Full name': 'Nom complet',
    'Work email': 'E-mail professionnel',
    'Confirm password': 'Confirmer le mot de passe',
    'Create account': 'Créer le compte',
    'Forgot your password?': 'Mot de passe oublié ?',
    'Reset password': 'Réinitialiser le mot de passe',
    'Send reset link': 'Envoyer le lien',
    'Check your inbox': 'Consultez votre boîte de réception',
    'Resend verification email': 'Renvoyer l’e-mail de vérification',
    'Messages': 'Messages',
    'Compose message': 'Rédiger un message',
    'Transactional SMS': 'SMS transactionnel',
    'Compose a message': 'Rédiger un message',
    'Recipient': 'Destinataire',
    'Schedule (optional)': 'Planification (facultatif)',
    'Send from': 'Envoyer depuis',
    'Any available SIM': 'Toute SIM disponible',
    'Message': 'Message',
    'Queue message': 'Mettre en file',
    'Delivery log': 'Journal de livraison',
    'All messages': 'Tous les messages',
    'Inbound': 'Entrants',
    'Recent replies': 'Réponses récentes',
    'From': 'Expéditeur',
    'Received': 'Reçu',
    'No inbound messages yet. Replies from your customers appear here.': 'Aucun message entrant. Les réponses de vos clients apparaîtront ici.',
    'Developer settings': 'Paramètres développeur',
    'Credentials': 'Identifiants',
    'Create an API key': 'Créer une clé API',
    'Key name': 'Nom de la clé',
    'Permissions': 'Autorisations',
    'Read messages': 'Lire les messages',
    'Send messages': 'Envoyer des messages',
    'Expiry (optional)': 'Expiration (facultatif)',
    'Create secret key': 'Créer la clé secrète',
    'Active credentials': 'Identifiants actifs',
    'API keys': 'Clés API',
    'Never used': 'Jamais utilisée',
    'Revoke': 'Révoquer',
    'External API': 'API externe',
    'Send and read messages': 'Envoyer et lire les messages',
    'Event delivery': 'Livraison des événements',
    'Add a webhook': 'Ajouter un webhook',
    'Name': 'Nom',
    'Events': 'Événements',
    'Create webhook': 'Créer le webhook',
    'Active endpoints': 'Points de terminaison actifs',
    'Overview': 'Vue d’ensemble',
    'Total messages': 'Total des messages',
    'All time': 'Depuis le début',
    'Delivered': 'Livrés',
    'In progress': 'En cours',
    'Online devices': 'Appareils en ligne',
    'Recent activity': 'Activité récente',
    'Latest messages': 'Derniers messages',
    'View all →': 'Tout afficher →',
    'Quick start': 'Démarrage rapide',
    'Connect your stack': 'Connectez votre système',
    'Workspace ready': 'Espace prêt',
    'Pair Android phone': 'Associer un téléphone Android',
    'Create API key': 'Créer une clé API',
    'Send first message': 'Envoyer le premier message',
    'Devices': 'Appareils',
    'Connect your phone in three steps': 'Connectez votre téléphone en trois étapes',
    'Create secure QR code': 'Créer un code QR sécurisé',
    'No phones connected': 'Aucun téléphone connecté',
    'Battery': 'Batterie',
    'Connection': 'Connexion',
    'Last seen': 'Dernière activité',
    'Revoke device': 'Révoquer l’appareil',
    'Plan & billing': 'Forfait et facturation',
    'Current subscription': 'Abonnement actuel',
    'Current plan': 'Forfait actuel',
    'messages used this period': 'messages utilisés sur cette période',
    'Activation history': 'Historique d’activation',
    'Plan requests': 'Demandes de forfait',
    'No plan changes requested.': 'Aucun changement de forfait demandé.',
    'Contacts': 'Contacts',
    'Consented': 'Consentis',
    'Opted out': 'Désabonnés',
    'Audience': 'Audience',
    'Add a contact': 'Ajouter un contact',
    'Phone (E.164)': 'Téléphone (E.164)',
    'Name (optional)': 'Nom (facultatif)',
    'Consent': 'Consentement',
    'Consent source': 'Source du consentement',
    'Save contact': 'Enregistrer le contact',
    'Recipients': 'Destinataires',
    'Outreach': 'Communication',
    'New campaign': 'Nouvelle campagne',
    'Campaign name': 'Nom de la campagne',
    'Launch campaign': 'Lancer la campagne',
    'History': 'Historique',
    'Campaigns': 'Campagnes',
    'No campaigns yet.': 'Aucune campagne.',
};

const patterns = [
    [/^Good to see you, (.+)\.$/, 'Ravi de vous revoir, $1.'],
    [/^(\d+) records$/, '$1 enregistrements'],
    [/^Latest (\d+)$/, '$1 derniers'],
    [/^(\d+) total$/, '$1 au total'],
    [/^Request (.+)$/, 'Demander $1'],
    [/^Used (.+)$/, 'Utilisée $1'],
];

const frenchAttributes = {
    'Search messages': 'Rechercher des messages',
    'e.g. Production integration': 'ex. Intégration de production',
    'e.g. Customer import': 'ex. Importation clients',
    'Describe this campaign': 'Décrivez cette campagne',
    'Copy': 'Copier',
    'Sign out': 'Se déconnecter',
};

export function localizePage() {
    if (document.documentElement.lang !== 'fr') return;
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
        acceptNode(node) {
            return ['SCRIPT', 'STYLE', 'CODE', 'PRE'].includes(node.parentElement?.tagName)
                ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT;
        },
    });
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach((node) => {
        const original = node.textContent;
        const trimmed = original.trim();
        if (! trimmed) return;
        let translated = french[trimmed];
        if (! translated) {
            for (const [pattern, replacement] of patterns) {
                if (pattern.test(trimmed)) {
                    translated = trimmed.replace(pattern, replacement);
                    break;
                }
            }
        }
        if (translated) node.textContent = original.replace(trimmed, translated);
    });

    document.querySelectorAll('[placeholder], [title], [aria-label]').forEach((element) => {
        ['placeholder', 'title', 'aria-label'].forEach((attribute) => {
            const original = element.getAttribute(attribute);
            if (original && frenchAttributes[original]) {
                element.setAttribute(attribute, frenchAttributes[original]);
            }
        });
    });
}
