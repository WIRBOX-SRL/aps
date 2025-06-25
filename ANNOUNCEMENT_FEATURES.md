# Funcționalități Anunțuri - Expirare și IP Restrictions

## 1. Expirare Automată a Anunțurilor

### Funcționalitate
- Anunțurile pot avea o dată de expirare (`expires_at`)
- Când anunțul expiră, statusul se schimbă automat în `draft`
- Expirarea se verifică automat la fiecare salvare

### Implementare
- **Model**: `App\Models\Announcement`
  - Metoda `shouldBeExpired()` - verifică dacă anunțul ar trebui să expire
  - Metoda `expire()` - marchează anunțul ca expirat
  - Scope `notExpired()` - filtrează anunțurile neexpirate
  - Boot method - verifică automat expirarea la salvare

- **Command**: `php artisan announcements:expire`
  - Rulează manual pentru a expira anunțurile
  - Poate fi programat cu cron

- **Job**: `App\Jobs\ExpireAnnouncementsJob`
  - Pentru procesare în background
  - Logging automat

### Utilizare în Filament
- Câmp `expires_at` în formularul de anunțuri
- Coloană în tabel cu status vizual (roșu pentru expirat)
- Filtru pentru anunțuri expirate

## 2. Limitare Acces IP

### Funcționalitate
- Anunțurile pot fi accesate doar de pe IP-uri specificate
- Limitare număr de accesuri per IP
- Tracking automat al accesurilor

### Implementare
- **Câmpuri în baza de date**:
  - `allowed_ips` (JSON) - array cu IP-uri permise
  - `max_ip_access_count` (INT) - număr maxim de accesuri per IP (0 = nelimitat)
  - `ip_access_log` (JSON) - log cu accesurile per IP

- **Model**: `App\Models\Announcement`
  - `isIpAllowed($ip)` - verifică dacă IP-ul este permis
  - `isIpAccessLimitReached($ip)` - verifică dacă limita de accesuri este atinsă
  - `canBeAccessedByIp($ip)` - verifică dacă IP-ul poate accesa anunțul
  - `logIpAccess($ip)` - înregistrează accesul IP
  - `getUniqueIpAccessCount()` - numărul de IP-uri unice
  - `getTotalIpAccessCount()` - numărul total de accesuri

- **Controller**: `App\Http\Controllers\AnnouncementController`
  - `show($id)` - afișează anunțul cu verificare IP
  - `checkAccess($id)` - verifică dacă IP-ul poate accesa
  - `stats($id)` - statistici acces IP

- **Middleware**: `App\Http\Middleware\CheckAnnouncementIpAccess`
  - Verifică automat IP-ul la accesarea anunțurilor
  - Logging automat al accesurilor

### Utilizare în Filament
- **Formular**:
  - Repeater pentru `allowed_ips` - adaugă IP-uri permise
  - Câmp numeric pentru `max_ip_access_count` - limitează accesurile

- **Tabel**:
  - Coloană `ip_access_info` - afișează statistici acces IP
  - Coloană `allowed_ips_count` - numărul de IP-uri permise

## 3. API Endpoints

### Rute disponibile
```php
GET /api/announcements/{id}           // Afișează anunțul cu verificare IP
GET /api/announcements/{id}/check-access  // Verifică dacă IP-ul poate accesa
GET /api/announcements/{id}/stats     // Statistici acces IP
```

### Exemplu de răspuns API
```json
{
  "announcement": {
    "id": 1,
    "title": "Tesla Model Y 2024",
    "price": 45000,
    "status": "published"
  },
  "access_info": {
    "your_ip": "192.168.1.100",
    "access_count": 3,
    "max_access_count": 5,
    "unique_ips": 2,
    "total_accesses": 7
  }
}
```

## 4. Configurare și Utilizare

### 1. Crearea unui anunț cu IP restrictions
1. Accesează `/admin/announcements/create`
2. Completează informațiile de bază
3. Setează `Expires At` pentru expirare automată
4. Adaugă IP-uri în `Allowed IP Addresses`
5. Setează `Max IP Access Count` (0 = nelimitat)

### 2. Programarea expirării automate
```bash
# Adaugă în crontab pentru verificare zilnică
0 2 * * * cd /path/to/project && php artisan announcements:expire

# Sau folosește job-ul în queue
php artisan queue:work
```

### 3. Testarea IP restrictions
```bash
# Testează accesul
curl http://your-domain.com/api/announcements/1/check-access

# Afișează anunțul
curl http://your-domain.com/api/announcements/1
```

## 5. Exemple de Utilizare

### Exemplu 1: Anunț cu expirare și IP restrictions
```php
$announcement = Announcement::create([
    'title' => 'Tesla Model Y 2024',
    'expires_at' => now()->addDays(30),
    'allowed_ips' => ['192.168.1.100', '10.0.0.50'],
    'max_ip_access_count' => 5,
    'status' => 'published'
]);
```

### Exemplu 2: Verificare acces IP
```php
$clientIp = request()->ip();
if ($announcement->canBeAccessedByIp($clientIp)) {
    $announcement->logIpAccess($clientIp);
    // Afișează anunțul
} else {
    abort(403, 'Access denied');
}
```

### Exemplu 3: Expirare automată
```php
// Se execută automat la fiecare salvare
if ($announcement->shouldBeExpired()) {
    $announcement->expire(); // Status devine 'draft'
}
```

## 6. Monitorizare și Logging

### Logs disponibile
- Accesuri IP în `ip_access_log`
- Expirări automate în `storage/logs/laravel.log`
- Statistici acces în tabelul Filament

### Comenzi utile
```bash
# Expiră anunțurile manual
php artisan announcements:expire

# Verifică anunțurile expirate
php artisan tinker
>>> App\Models\Announcement::where('expires_at', '<=', now())->get()
``` 
