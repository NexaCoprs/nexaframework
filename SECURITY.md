# Guide de Sécurité - Nexa Framework

## 🔒 Configuration de Sécurité sur OVH

### Dossiers et Permissions

#### ✅ Dossiers ACCESSIBLES depuis le web :

```
public/                 # Racine web - 755
├── index.php          # Point d'entrée - 644
├── .htaccess          # Configuration Apache - 644
├── web.config         # Configuration IIS - 644
├── assets/            # Ressources statiques - 755
├── css/               # Feuilles de style - 755
├── js/                # Scripts JavaScript - 755
└── uploads/           # Fichiers utilisateurs - 775 (écriture requise)
```

#### ❌ Dossiers NON ACCESSIBLES depuis le web :

```
config/                # Configuration - 750
├── app.php           # Config principale - 640
├── database.php      # Config BDD - 640
└── production.php    # Config production - 640

src/                  # Code source - 750
app/                  # Application - 750
storage/              # Stockage - 750
├── logs/             # Logs - 750
└── cache/            # Cache - 750

examples/             # Exemples de code - 750
tests/                # Tests - 750
vendor/               # Dépendances - 750
```

### Configuration Apache (.htaccess)

#### Dans le dossier racine :
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Redirection vers public/
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ public/$1 [L]
    
    # Bloquer l'accès aux dossiers sensibles
    RewriteCond %{REQUEST_URI} ^/(config|src|storage|vendor|app|database|tests)/
    RewriteRule ^.*$ - [F,L]
</IfModule>
```

#### Dans public/.htaccess :
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>

# Sécurité supplémentaire
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

<Files "composer.*">
    Order allow,deny
    Deny from all
</Files>
```

## 🚨 Points de Sécurité Critiques

### 1. Variables d'Environnement
- ✅ Fichier `.env` doit être hors du dossier `public/`
- ✅ Ne jamais commiter `.env` dans Git
- ✅ Utiliser des mots de passe forts

### 2. Permissions de Fichiers
```bash
# Commandes pour OVH (via SSH si disponible)
chmod 755 public/
chmod 775 public/uploads/
chmod 750 config/ src/ app/ storage/
chmod 640 config/*.php
chmod 644 public/index.php public/.htaccess
```

### 3. Protection des Dossiers
- ❌ **JAMAIS** rendre accessible : `config/`, `src/`, `storage/`, `vendor/`
- ✅ Seul `public/` doit être accessible depuis le web
- ✅ Utiliser les fichiers `.htaccess` pour bloquer l'accès

### 4. Uploads de Fichiers
- ✅ Valider les types de fichiers
- ✅ Limiter la taille des uploads
- ✅ Scanner les fichiers pour les virus
- ❌ Ne jamais exécuter les fichiers uploadés

### 5. Configuration OVH

#### Option A : Racine web sur public/
```
Dossier web : /public/
Structure :
├── public/     <- Racine web OVH
├── config/
├── src/
└── ...
```

#### Option B : Redirection depuis la racine
```
Dossier web : /
Fichiers de redirection :
├── .htaccess           <- Redirige vers public/
├── index_redirect.php  <- Alternative PHP
├── public/
└── ...
```

## 🔍 Vérification de Sécurité

### Tests à effectuer :

1. **Accès aux dossiers sensibles** :
   - `https://votre-site.com/config/` → Doit retourner 403/404
   - `https://votre-site.com/src/` → Doit retourner 403/404
   - `https://votre-site.com/storage/` → Doit retourner 403/404

2. **Accès aux fichiers sensibles** :
   - `https://votre-site.com/.env` → Doit retourner 403/404
   - `https://votre-site.com/composer.json` → Doit retourner 403/404

3. **Fonctionnement normal** :
   - `https://votre-site.com/` → Page d'accueil
   - `https://votre-site.com/examples` → Page exemples (vue)
   - `https://votre-site.com/uploads/` → Dossier uploads (si configuré)

## 📞 Support

En cas de problème de sécurité :
1. Vérifiez les permissions des fichiers
2. Contrôlez la configuration `.htaccess`
3. Testez les accès interdits
4. Contactez le support OVH si nécessaire