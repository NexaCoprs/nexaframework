# Guide de Déploiement - Nexa Framework

## Problème résolu

Le problème où seule la page d'accueil fonctionnait en production a été résolu en ajoutant les fichiers de configuration serveur nécessaires.

## Fichiers ajoutés

### 1. `.htaccess` (pour serveurs Apache)
Fichier: `public/.htaccess`
- Redirige toutes les requêtes vers `index.php`
- Gère les URL propres
- Supprime les slashes de fin

### 2. `web.config` (pour serveurs IIS/Windows)
Fichier: `public/web.config`
- Configuration pour serveurs Windows/IIS
- Équivalent du `.htaccess` pour IIS

### 3. Configuration de production
Fichier: `config/production.php`
- Mode debug désactivé
- Logging optimisé pour la production

## Instructions de déploiement sur OVH

### 1. Structure des fichiers

#### Option A: Configuration idéale (dossier public comme racine web)
Assurez-vous que votre structure de fichiers sur le serveur est :
```
/
├── public/          <- Dossier racine web (www ou public_html)
│   ├── .htaccess
│   ├── web.config
│   ├── index.php
│   └── ...
├── app/
├── config/
├── src/
└── ...
```

#### Option B: Si vous ne pouvez pas configurer la racine web
Utilisez les fichiers de redirection à la racine :
```
/                    <- Dossier racine web (www ou public_html)
├── .htaccess        <- Redirige vers public/
├── index_redirect.php <- Alternative de redirection
├── public/
│   ├── .htaccess
│   ├── web.config
│   ├── index.php
│   └── ...
├── app/
├── config/
├── src/
└── ...
```

### 2. Configuration du serveur web

#### Pour Apache (le plus courant sur OVH)
- Le fichier `.htaccess` est automatiquement pris en compte
- Assurez-vous que `mod_rewrite` est activé

#### Pour IIS/Windows
- Le fichier `web.config` sera utilisé automatiquement

### 3. Permissions
Assurez-vous que les dossiers suivants sont accessibles en écriture :
```bash
chmod 755 storage/
chmod 755 storage/logs/
chmod 755 storage/cache/
chmod 755 storage/framework/
chmod 755 storage/framework/views/
```

### 4. Variables d'environnement
Créez un fichier `.env` à la racine avec :
```
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
```

### 5. Configuration des redirections (Option B)
Si vous utilisez l'Option B avec les fichiers de redirection :

1. **Fichier .htaccess à la racine** :
   - Redirige automatiquement toutes les requêtes vers le dossier `public/`
   - Bloque l'accès direct aux dossiers sensibles (config, src, etc.)

2. **Fichier index_redirect.php** :
   - Alternative PHP si .htaccess ne fonctionne pas
   - Peut être renommé en `index.php` si nécessaire
   - Gère les redirections au niveau PHP

3. **Utilisation** :
   ```bash
   # Si .htaccess fonctionne, rien à faire de plus
   # Si .htaccess ne fonctionne pas, renommez :
   mv index_redirect.php index.php
   ```

### 6. Configuration des dossiers accessibles

**Important** : Distinction entre les dossiers :

1. **Dossier `public/`** :
   - Seul dossier accessible depuis le web
   - Contient `index.php`, assets, CSS, JS
   - Dossier `public/uploads/` pour les fichiers utilisateurs

2. **Dossier `examples/`** :
   - Contient les exemples de code PHP
   - **NON accessible depuis le web** (sécurité)
   - Utilisé uniquement pour le développement

3. **Route `/examples`** :
   - Affiche la vue `resources/views/examples.nx`
   - Accessible via l'URL : `https://votre-domaine.com/examples`
   - Différent du dossier `examples/`

### 7. Test des routes
Après déploiement, testez ces URLs :
- `https://votre-domaine.com/` (page d'accueil)
- `https://votre-domaine.com/about` (page à propos)
- `https://votre-domaine.com/documentation` (documentation)
- `https://votre-domaine.com/examples` (page exemples - vue)
- `https://votre-domaine.com/tutorials` (tutoriels)
- `https://votre-domaine.com/contact` (contact)

## Débogage

Si les problèmes persistent :

1. **Vérifiez les logs** dans `storage/logs/app.log`
2. **Activez temporairement le debug** en modifiant `config/app.php` :
   ```php
   'debug' => true,
   ```
3. **Vérifiez la configuration du serveur** avec votre hébergeur OVH
4. **Testez les deux options de redirection** (A et B)

## Support

Si vous rencontrez encore des problèmes :
1. Vérifiez que le dossier `public` est bien configuré comme racine web
2. Contactez le support OVH pour vérifier la configuration Apache/PHP
3. Assurez-vous que PHP 7.4+ est installé

## Améliorations apportées

- ✅ Ajout du fichier `.htaccess` pour la réécriture d'URL
- ✅ Ajout du fichier `web.config` pour les serveurs IIS
- ✅ Amélioration du logging et gestion d'erreurs
- ✅ Configuration de production optimisée
- ✅ Sécurisation des messages d'erreur en production