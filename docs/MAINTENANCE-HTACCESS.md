# Example .htaccess snippet for simple maintenance page
RewriteEngine On
RewriteCond %{REQUEST_URI} !/maintenance.html$
RewriteCond %{REMOTE_ADDR} !^127\.0\.0\.1$
RewriteRule ^(.*)$ /maintenance.html [R=302,L]
