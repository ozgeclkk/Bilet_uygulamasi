# Otobüs Bileti Uygulaması

Bu proje, PHP tabanlı basit bir **otobüs bileti rezervasyon sistemi** örneğidir.  
Proje, **Docker** kullanılarak kolayca konteyner ortamında çalıştırılabilir.

---

## Özellikler

- Kullanıcı kayıt ve giriş sistemi  
- Otobüs seferi listeleme  
- Koltuk seçimi ve bilet oluşturma  
- Admin paneli 
- SQLite desteği  
- Apache + PHP + Docker altyapısı  

---

## Teknolojiler

- **PHP 8.2**
- **Apache**
- **SQLite**
- **Docker & Docker Compose**
- **HTML, CSS, JavaScript**

---

## Docker ile Çalıştırma

Projeyi Docker üzerinde çalıştırmak için aşağıdaki adımları izleyin:

```bash
# 1. Proje dizinine gidin
cd otobus-bileti

# 2. Docker konteynerini başlatın
docker compose up --build
```
http://localhost:8080
