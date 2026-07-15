# CONTINUATION.md — Hướng dẫn tiếp tục

Khi nhận lệnh "Tiếp tục", đọc theo thứ tự:

1. `PROJECT_PROGRESS.md` — biết chính xác cái gì đã xong, cái gì dở dang, nợ kỹ thuật gì đang có.
2. `NEXT_TASK.md` — biết việc tiếp theo cụ thể là gì và vì sao nó được ưu tiên.
3. `01_ARCHITECTURE_REPORT.md` + `02_DEVELOPMENT_ROADMAP.md` — nhắc lại kiến trúc tổng thể và toàn bộ milestone còn lại, để không đi lệch hướng.
4. Toàn bộ Markdown gốc trong project + Skills — vẫn là nguồn sự thật duy nhất khi có mâu thuẫn.

## Quy tắc khi tiếp tục

- Không hỏi lại người dùng nên làm gì tiếp theo — tự quyết định dựa trên dependency graph đã nêu trong Architecture Report.
- Không lặp lại việc đã làm — kiểm tra `PROJECT_PROGRESS.md` trước khi viết code mới.
- Nếu phát hiện file đã tạo trước đó có lỗi/thiếu sót khi review lại — sửa ngay, ghi chú lại trong `PROJECT_PROGRESS.md` phần "RỦI RO / NỢ KỸ THUẬT".
- Sau khi hoàn thành một module: review, refactor, kiểm tra brace-balance thủ công (không có PHP CLI trong sandbox), cập nhật `README.md` + `PROJECT_PROGRESS.md` + `NEXT_TASK.md`, rồi mới sang module tiếp theo.
- Không dừng lại để hỏi xác nhận giữa các module — chỉ dừng khi hết context/token, và phải cập nhật 3 file này trước khi dừng.
- Khi đóng gói ZIP mới: tăng số phiên bản trong tên file (`hoahoconga-phaseN-*.zip`), giữ lại các file kiến trúc/roadmap ở ngoài thư mục `project/`.

## Trạng thái container/sandbox

Môi trường làm việc hiện tại **không có PHP CLI, không có MySQL** — không
thể chạy migration/seeder hay `php -l` thật để kiểm chứng. Toàn bộ kiểm tra
chất lượng hiện tại là review thủ công + kiểm tra ngoặc cân bằng bằng script
bash. Nếu môi trường tương lai có PHP CLI, hãy chạy ngay:

```bash
find . -name "*.php" -exec php -l {} \;
```

trước khi tiếp tục viết code mới, để bắt các lỗi cú pháp có thể đã lọt qua.
