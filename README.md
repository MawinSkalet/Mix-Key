HOW TO SET UP PROJECT
1.FOLK THIS REPO
2.OPEN IN IDE
3.DOWNLOAD Makefiles Tools extentions ใน vscode 
4.เปิด docker desktop
5.เปิด terminal แล้วพิม make setup หรือ ใช้คำสั่งนี้ 
docker-compose up -d --build
docker-compose exec backend composer install
docker-compose exec backend php artisan key:generate
docker-compose exec backend php artisan migrate:fresh --seed
docker-compose exec frontend npm install
6.เปลี่ยน file ลบ .exam ในทุกไฟล์ แล้ว ตั้งค่า environtment
