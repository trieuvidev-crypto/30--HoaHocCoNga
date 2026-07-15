# PROJECT_PROGRESS.md — HoaHocCoNga.Com

Cập nhật lần cuối: sau khi hoàn thành Phase 2 (RBAC runtime) + Phase 3 (Core System Services).

## Trạng thái tổng quan

Đang triển khai theo `02_DEVELOPMENT_ROADMAP.md`. Không lệch thứ tự phụ thuộc.
Toàn bộ code trong danh sách "ĐÃ XONG" bên dưới là code thật, không có
placeholder, không có TODO, đã qua kiểm tra brace-balance và review thủ công
(không có môi trường PHP CLI trong sandbox nên không chạy được `php -l`
hay test thật — cần chạy `php app/Console/migrate.php` + `php -l` trên máy
có PHP trước khi deploy production).

## ĐÃ XONG

### Phase 1 — Foundation
- [x] M1.1 Bootstrap (`bootstrap/app.php`, `bootstrap/helpers.php`, autoload, config loader)
- [x] M1.2 Router + middleware pipeline (`app/Core/Router.php`)
- [x] M1.3 Schema nền tảng (7 migration: Auth, RBAC, System Reference, Media, Course/Chapter/Lesson, Chemistry, Payment)
- [x] M1.4 Seeder thật (roles/permissions, grades/subjects/provinces, chemistry — 20 elements/20 compounds/6 reactions)

### Phase 2 — Auth, Authorization, Security Core
- [x] M2.1 Authentication đầy đủ (register/login/refresh/logout/forgot/reset — `AuthService`, `AuthController`, `UserRepository`, `TokenService`)
- [x] M2.2 RBAC runtime enforcement (`PermissionService` có cache + role_hierarchy, `PermissionMiddleware`, `Policy` base + `CoursePolicy`)
- [x] M2.3 Session/Token security — JWT + CSRF + Rate Limit + **session-based login cho web đã xong** (`AuthService::login($useSession)`, session_regenerate_id chống fixation, logout xóa cả session lẫn refresh token)

### Phase 3 — Core System Services
- [x] M3.1 Cache (`App\Core\Cache`, file-based, TTL, remember())
- [x] M3.2 Event/Hook system (`App\Core\Events\*`, dispatcher + đăng ký tại `bootstrap/events.php`, đã nối vào AuthService)
- [x] M3.3 Notification core — `NotificationService` (in-app + email theo preference), `MailerService` (SMTP tự viết có STARTTLS/AUTH LOGIN, fallback `mail()` PHP built-in cho cPanel), `NotificationRepository`, migration 0008. `AuthService::requestPasswordReset` giờ gửi email thật.
- [x] M3.4 Media/Upload service (`MediaUploadService`, `MediaRepository`, validate MIME thật + chống trùng lặp SHA-256)
- [x] M3.5 Logging (`App\Core\Logger`, JSON theo category, tự redact dữ liệu nhạy cảm)

### Phase 8 (một phần, làm sớm để giải quyết nợ kỹ thuật)
- [x] `BankQrDriver` + `VietQrPayloadBuilder` — sinh mã QR chuyển khoản chuẩn VietQR/EMVCo thật (TLV + CRC16-CCITT đúng thuật toán), driver duy nhất hoạt động thật trong `config/payment.php`. **Chưa có** `PaymentService`/`OrderController` gọi tới driver này — đó vẫn là việc của Phase 8 đầy đủ.

### Phase 5 — Course/Chapter/Lesson (Teacher + Admin)
- [x] `CourseRepository`, `ChapterRepository`, `LessonRepository` — data access thuần, không business logic.
- [x] `CourseService` (create/update/publish/archive/duplicate/delete) — dùng `CoursePolicy` chặn quyền sở hữu, dùng `SlugGenerator` (mới, tự viết, xử lý dấu tiếng Việt) sinh slug duy nhất, invalidate cache listing qua `Cache::forgetPrefix()`.
- [x] `ChapterService`, `LessonService` — đầy đủ CRUD + reorder, kiểm tra chapter/lesson thuộc đúng course trước khi thao tác.
- [x] `CourseController`, `ChapterController`, `LessonController` — mỏng, map RuntimeException message sang HTTP status (404/403/422) theo tiền tố tiếng Việt.
- [x] Route thật đã đăng ký trong `routes/api.php` (trước đây chỉ là comment mẫu) — `PermissionMiddleware` áp cho từng action theo đúng permission slug đã seed ở Phase 1 (`courses.create`, `courses.edit`, `courses.publish`, `courses.delete`, `lessons.create`, `lessons.edit`).
- [x] `LessonCreatedEvent` + `NotifyEnrolledStudentsListener` — khi giáo viên thêm bài học mới vào khóa học **đã xuất bản**, mọi học viên đang ghi danh nhận thông báo qua `NotificationService` đã có sẵn từ Phase 3. Đăng ký tại `bootstrap/events.php`.
- [x] `CourseCreatedEvent`, `CoursePublishedEvent` — đã dispatch, chưa có listener riêng (không cần thiết ngay, không tạo "chay" khi đã có ít nhất một use case rõ ràng — xem comment trong `bootstrap/events.php`).

## 🔬 PHIÊN KIỂM THỬ TÍCH HỢP THẬT (MySQL 8 + PHP 8.3 CLI + PHP built-in server)

Sandbox bất ngờ có sẵn `apt install php-cli mysql-server` — đã tận dụng để chạy
**toàn bộ hệ thống thật**: migration thật, seeder thật, HTTP server thật, và
test bằng `curl` thật qua từng luồng nghiệp vụ. Đây là lần đầu tiên dự án được
kiểm chứng bằng thực thi thay vì chỉ review tĩnh — và đã phát hiện **6 bug thật
mà review tĩnh hoàn toàn bỏ sót**:

1. **`public/index.php` gọi `session_start()` trước khi `bootstrap/app.php` set `session_set_cookie_params()`/`session_name()`** — PHP cấm đổi cấu hình session sau khi đã start. Sinh warning ở mọi request. **Đã sửa**: chuyển `session_start()` vào đúng sau đoạn set cookie params trong `bootstrap/app.php`.
2. **`Request::header()` so khớp tên header phân biệt hoa/thường**, trong khi `extractHeaders()` chuẩn hóa "X-CSRF-Token" thành "X-Csrf-Token" (do `ucwords()` viết hoa từng từ). Config tra `'X-CSRF-Token'` → luôn `null` → **CSRF luôn thất bại với mọi request có gửi token đúng**. Đây là bug nghiêm trọng nhất tìm được — chặn hoàn toàn đăng ký/đăng nhập qua form thật. **Đã sửa**: `header()` giờ lowercase cả key lưu trữ lẫn key tra cứu (đúng chuẩn HTTP header case-insensitive).
3. **Đăng ký xong không thể đăng nhập được — thiếu hoàn toàn luồng xác minh email.** `AuthService::register()` tạo user với `status='pending'`, `login()` chặn `status='pending'`, nhưng **không hề có endpoint hay logic nào chuyển `pending` → `active`**. Đây là lỗ hổng chức năng cốt lõi mà tôi đã báo cáo nhầm là "hoàn chỉnh" ở các lượt trước. **Đã sửa**: `AuthService::sendVerificationEmail()`/`verifyEmail()`/`resendVerificationEmail()`, route `POST /api/v1/auth/verify-email`, trang web `/verify-email` xử lý link trong email, cùng `resend-verification` cho trường hợp token hết hạn.
4. **`resendVerificationEmail(): void` có dòng `return $user;` sót lại** (copy-paste) — lỗi cú pháp thật, bắt được bằng `php -l`.
5. **`LessonRepository::create()` dùng trùng tên tham số `:created_by` cho cả cột `created_by` và `updated_by`** trong cùng một câu SQL — PDO với `ATTR_EMULATE_PREPARES=false` không hỗ trợ đáng tin cậy việc tái sử dụng named parameter, gây lỗi "Invalid parameter number" **thật sự khi tạo bài học qua HTTP**. Đã rà soát lại toàn bộ 35 file Repository/Service bằng PHP tokenizer thật (không phải regex đoán mò) — xác nhận không còn instance nào khác của lỗi này.
6. **`App\Core\Router::addRoute()` không chuẩn hóa dấu `/` cuối** khi pattern được ghép từ nhiều group prefix lồng nhau + route `'/'` (ví dụ `/administrator` + `/payments` + `/` → `/administrator/payments/`), trong khi `Request::path()` luôn strip trailing slash khỏi path thực tế đến — route không bao giờ khớp. Phát hiện khi xây trang `/administrator/payments`. **Đã sửa**: `addRoute()` giờ chuẩn hóa y hệt `Request::path()`.

**Đã kiểm chứng đúng bằng thực thi** (không chỉ "code trông có vẻ đúng"):
- Toàn bộ 8 migration chạy thành công trên MySQL 8 thật → 64 bảng được tạo đúng.
- Toàn bộ 4 seeder chạy thành công → dữ liệu thật (7 role, 48 quyền, 20 nguyên tố, 20 hợp chất, 6 phản ứng, 1 tài khoản ngân hàng demo).
- **Luồng đăng ký → xác minh email → đăng nhập → session → `/dashboard`** chạy đúng end-to-end qua HTTP thật.
- **Cân bằng phương trình** `H2 + O2 → H2O` qua API thật trả về đúng `2H2 + O2 → 2H2O`.
- **Molar mass** `H2SO4` qua API thật trả về `98.072 g/mol` (khớp giá trị khoa học thật).
- **VietQR QR payload**: đã trích xuất payload thật từ API và **kiểm chứng độc lập CRC16-CCITT bằng một implementation Python riêng biệt** — khớp chính xác 100%. Xác nhận `VietQrPayloadBuilder` đúng chuẩn EMVCo thật, không chỉ "chạy không lỗi".
- **Luồng Teacher**: tạo khóa học (slug tiếng Việt tự động đúng: "Hóa học 10 - Chương Nguyên tử" → `hoa-hoc-10-chuong-nguyen-tu`), xuất bản, thêm chương, thêm bài học — tất cả qua HTTP thật.
- **Luồng thương mại đầy đủ**: học sinh checkout → tạo order + payment Bank QR → admin xác nhận qua UI thật → xác nhận trong DB: `order.status='paid'`, `payment.status='paid'`, `course_enrollments` có 1 dòng mới, `notifications` có 1 dòng mới. Toàn bộ chuỗi event (`PaymentCompletedEvent` → `GrantCourseAccessListener`) hoạt động đúng.

**Phát hiện thêm (không phải bug code, nhưng là nợ kỹ thuật cần lưu ý)**:
- `PermissionService` cache quyền hạn theo user trong 300s. Khi gán role mới cho user đã có cache cũ (ví dụ thao tác trực tiếp DB khi test), quyền mới **không có hiệu lực ngay** — phải đợi hết TTL hoặc gọi `invalidateUserCache()`. **Bất kỳ tính năng "Admin gán role cho user" nào xây sau này BẮT BUỘC phải gọi `PermissionService::invalidateUserCache($userId)`** ngay sau khi sửa `user_roles`, nếu không sẽ có độ trễ tối đa 5 phút gây khó hiểu cho admin.
- `payment_bank_accounts`/QR hết hạn sau 30 phút theo đúng thiết kế (`confirmation_expiry_minutes`) — không phải bug, nhưng cần nhớ khi test thủ công.

**Môi trường test đã dọn dẹp hoàn toàn trước khi đóng gói**: xóa `.env` thật (chứa mật khẩu test), `vendor/`, `composer.lock`, `storage/logs/*.log`, `storage/cache/*.cache`, `nodeapp/node_modules`, và 2 thư mục rác từ brace-expansion lỗi (`{app`, `nodeapp/{config,...}` — không có file bên trong, chỉ là thư mục rỗng do `/bin/sh` không hỗ trợ cú pháp `{a,b,c}` của bash).

- **`Cache::forgetPrefix()`** đọc `$meta['key']` để so khớp tiền tố, nhưng `Cache::put()` (viết ở lượt trước) không hề lưu `key` vào envelope — nghĩa là invalidate theo prefix sẽ luôn no-op một cách âm thầm. Đã sửa `put()` để lưu `key` vào envelope. Đây là kiểu lỗi khó phát hiện nếu không chủ động review lại code cũ khi module mới bắt đầu dùng đến nó — bài học: mỗi khi một service mới gọi vào một hàm Core đã viết từ trước, phải đọc lại toàn bộ implementation của hàm đó, không giả định nó đúng.
- **Trùng lặp bảng ánh xạ bỏ dấu tiếng Việt** ở 3 nơi (`SlugGenerator`, `ChemistryCompoundService`, seeder 0003) — vi phạm trực tiếp "never duplicate code". Đã trích xuất thành `App\Core\VietnameseTextNormalizer` dùng chung; cả 3 nơi và seeder giờ gọi cùng một hàm, đảm bảo kết quả chuẩn hóa luôn khớp nhau giữa lúc seed và lúc tra cứu.
- **`EquationBalancerService::rowEchelonForm()`** có một dòng return tự tham chiếu vô nghĩa (`[$matrix, ..., $pivotColumns][0] === $matrix ? ... : ...` — luôn đúng, hai nhánh giống hệt nhau) sót lại từ lúc viết nháp thuật toán. Đã dọn thành return đơn giản. Đã trace tay thuật toán trên ví dụ H₂ + O₂ → H₂O để xác nhận kết quả đúng (2H₂ + O₂ → 2H₂O) trước khi coi module hoàn thành.
- **`OrderService::applyCoupon()`** ban đầu tính discount nhưng quên ghi `coupon_usage` + tăng `used_count` — nghĩa là giới hạn lượt dùng mã giảm giá sẽ không bao giờ được thực thi. Đã sửa, đồng thời bọc toàn bộ `createFromCourse()` trong transaction để đơn hàng + order_item + coupon_usage luôn nhất quán.

### Phase 7 — Node.js/Socket.IO server
- [x] `nodeapp/server.js`, `nodeapp/socket.js`, `nodeapp/internalBridge.js` — Socket.IO server + cầu nối nội bộ (PHP → Node) trên `http` built-in, không dùng Express (giữ đúng nguyên tắc phụ thuộc tối thiểu).
- [x] `nodeapp/authentication/verifyJwt.js` — xác thực JWT HS256 tự viết bằng `crypto` built-in, khớp chính xác định dạng của `App\Services\Auth\TokenService` (PHP).
- [x] `nodeapp/rooms/roomNames.js`, `nodeapp/services/presenceTracker.js` — quy ước room + theo dõi online/offline trong bộ nhớ (không Redis).
- [x] `nodeapp/handlers/connectionHandlers.js` — join room theo role (admin/teacher) đọc từ JWT payload, broadcast `user.online`/`user.offline` cho phòng admin.
- [x] **PHP → Node**: `App\Services\Realtime\RealtimeBroadcastService` (fire-and-forget, không bao giờ làm chậm/lỗi request PHP nếu Node down) đã được nối vào `NotificationService::notify()` — mọi thông báo in-app giờ đẩy realtime tới trình duyệt ngay lập tức, không chỉ nằm chờ ở lần tải trang sau.
- [x] Đã nhúng `roles` vào JWT access token (`AuthService::login()` và `refresh()`) — phát hiện thiếu khi viết `connectionHandlers.js` cần đọc role từ token mà không query lại DB.

**QUAN TRỌNG — đây là module đầu tiên được kiểm thử tích hợp thật, không chỉ review tĩnh**, vì sandbox có sẵn Node.js v22 + npm:
- Đã `npm install` thật (socket.io + socket.io-client), `node --check` toàn bộ file — không lỗi cú pháp.
- Đã khởi động server thật, test cầu nối nội bộ bằng `curl`: thiếu secret → 401, sai secret → 401, đúng secret → 200 với broadcast thành công.
- Đã dùng `socket.io-client` thật để test handshake: token hợp lệ (role admin) → kết nối + join room đúng; token hết hạn → từ chối; **chữ ký bị giả mạo** → từ chối; không có token → từ chối. Toàn bộ 4 test case đều đúng như thiết kế.
- Môi trường test đã được dọn dẹp (`node_modules/`, file test tạm) trước khi đóng gói — không có trong ZIP giao nộp; chạy `npm install` trong `nodeapp/` trước khi `node server.js`.

### Phase 8 — Order/Payment (Bank QR end-to-end)
- [x] `OrderRepository`, `PaymentRepository` — data access thuần.
- [x] `OrderService::createFromCourse()` — kiểm tra course đã publish, chưa enroll trùng, áp dụng coupon có validate đầy đủ (thời hạn, giá trị tối thiểu, giới hạn lượt dùng tổng + theo user), tự động set order status='paid' nếu tổng tiền = 0 (khóa học miễn phí/giảm 100%).
- [x] `PaymentService::initiate()` — gọi `BankQrDriver` đã có sẵn, tái sử dụng payment pending còn hạn thay vì sinh QR mới liên tục.
- [x] `PaymentService::confirmManually()` — luồng xác nhận thủ công duy nhất cho Bank QR (không có callback tự động), transaction-safe, dispatch `PaymentCompletedEvent`.
- [x] `GrantCourseAccessListener` — lắng nghe `PaymentCompletedEvent`, tự tạo `course_enrollments`, tăng `enrollment_count`, gửi thông báo qua `NotificationService` đã có — **không** có logic cấp quyền nào nằm trong PaymentService, đúng nguyên tắc tách module qua event.
- [x] `OrderController` (checkout, học sinh), `Admin\PaymentController` (danh sách chờ xác nhận + confirm/reject) — route thật trong `routes/api.php` + `routes/admin.php`.
- [x] Seeder `payment_bank_accounts` (1 tài khoản DEMO — **bắt buộc đổi trước khi vận hành thật**, đã ghi rõ cảnh báo trong seeder).

### Phase 6 — Chemistry Engine logic
- [x] `ChemicalFormulaParser` — parser đệ quy xử lý ngoặc lồng nhau (Ca(OH)₂, Al₂(SO₄)₃), không phải regex đơn giản.
- [x] `Fraction` — số hữu tỉ chính xác (tử số/mẫu số nguyên, tự động rút gọn) để tránh sai số dấu phẩy động khi cân bằng phương trình.
- [x] `EquationBalancerService` — cân bằng phương trình bằng khử Gauss thật trên ma trận hệ số nguyên tố, phát hiện đúng trường hợp vô nghiệm/nhiều nghiệm độc lập thay vì trả kết quả sai. Đã kiểm chứng bằng tay.
- [x] `ChemistryCalculatorService` — molar mass (tính từ dữ liệu nguyên tố đã seed, không hardcode), pH, pha loãng (C₁V₁=C₂V₂, giải cho ẩn bất kỳ trong 4 biến), stoichiometry cơ bản. Mỗi kết quả đều kèm formula + steps + explanation theo đúng yêu cầu `CHEMISTRY_DOMAIN.md`.
- [x] `ChemistryCompoundService` — tra cứu theo formula/UUID, tìm kiếm alias chịu lỗi chính tả (khớp chính xác trước, LIKE fallback sau).
- [x] `ChemistryRepository` — data access cho elements/compounds/aliases/reactions/participants.
- [x] `ChemistryController` + route công khai `/api/v1/chemistry/*` (không cần đăng nhập — công cụ Hóa học là nội dung SEO/marketing công khai theo `HOME_PAGE_SPEC.md`).

### Phase 6 (tiếp) — Question Bank + Quiz Engine
- [x] Migration 0009: `question_bank`, `question_options`, `question_categories`, `question_tags`, `question_reports`, `quizzes`, `quiz_questions`, `quiz_attempts`, `quiz_attempt_answers` — chạy thành công trên MySQL thật ngay lần đầu.
- [x] `QuestionRepository`, `QuizRepository`, `QuizAttemptRepository` — data access thuần.
- [x] `QuestionService` — validate câu hỏi trắc nghiệm (tối thiểu 2 lựa chọn, đúng số đáp án đúng theo loại single/multiple/true-false).
- [x] `QuizService` — tạo quiz, gán/gỡ câu hỏi, xuất bản (chặn nếu chưa có câu hỏi nào).
- [x] **`QuizAttemptService` — lõi chấm điểm tự động**: single/multiple_choice/true_false so khớp tập hợp đáp án chọn với tập đáp án đúng (không có điểm một phần — quy tắc rõ ràng, có ghi chú); fill_blank/calculation so khớp chính xác chuỗi (có ghi rõ giới hạn: không hiểu tương đương số học "2" vs "2.0"); essay không tự chấm (để trống chờ giáo viên chấm tay — chưa xây ở phase này). Chặn `max_attempts`, tính `expires_at` theo `time_limit_minutes`, **không bao giờ lộ `is_correct` cho học sinh khi đang làm bài** (đã kiểm chứng qua HTTP thật).
- [x] `QuestionController`, `QuizController`, `QuizAttemptController` + route đầy đủ trong `routes/quiz.php`.

**Đã kiểm chứng bằng thực thi thật, đầy đủ vòng đời**:
- Giáo viên: tạo câu hỏi trắc nghiệm Hóa học thật ("Nguyên tử X có 17 proton...") → xuất bản → tạo quiz → gán câu hỏi → xuất bản quiz. Tất cả qua HTTP thật.
- Học sinh: bắt đầu làm bài → nhận câu hỏi kèm lựa chọn **không lộ đáp án đúng** (đã tự kiểm tra JSON response, xác nhận `options` không có field `is_correct`) → trả lời đúng → nộp bài → **kết quả 100%, đậu**.
- Lượt 2: trả lời sai → nộp bài → **kết quả 0%, rớt** — xác nhận logic chấm điểm phân biệt đúng/sai chính xác.
- Lượt 3: bị chặn đúng theo `max_attempts=2` với thông báo tiếng Việt rõ ràng.

**Bug thật phát hiện và sửa ngay trong lúc test**: `QuestionRepository::create()` dùng trùng `:creator` cho cả `created_by` và `updated_by` — **cùng lớp lỗi y hệt** đã gặp ở `LessonRepository` trước đây (PDO native-prepare không hỗ trợ tái sử dụng named parameter). Đã chạy lại scanner tokenizer trên toàn bộ Repository/Service — xác nhận không còn instance nào khác trong dự án. Bài học rút ra: đây là một **lỗi hệ thống** (systematic mistake) tôi hay mắc khi viết nhanh nhiều câu INSERT có `created_by`/`updated_by` — cần đặc biệt cẩn thận với 2 cột này ở mọi Repository mới.

- Phase 4 (phần còn lại): Admin Console shell (xác nhận thanh toán qua UI — API đã có ở Phase 8), trang chủ công khai thật theo `HOME_PAGE_SPEC.md` (hiện `HomeController::index()` vẫn là HTML tối giản).
- Phase 6 (phần còn lại): Question Bank, Quiz Engine, Flashcards, AI Tutor
- Phase 7 (phần còn lại): Realtime Chat, Realtime Forum, Realtime Live Class chi tiết
- Phase 8 (phần còn lại): Invoice PDF generation, Refund workflow admin UI, revenue analytics
- Phase 9-12: SEO, Search, UI polish, Optimization, Production

### Phase 4 (lát cắt đầu tiên) — Design System + trang Đăng nhập/Đăng ký
- [x] `public/assets/css/tokens.css` — design token đầy đủ (màu, spacing 8-point, radius, shadow, typography, motion) theo đúng giá trị trong `DESIGN_SYSTEM_2026.md`, hỗ trợ dark mode qua `[data-theme]` không cần reload trang.
- [x] `public/assets/css/base.css`, `components.css` — reset + component tái sử dụng (button có loading state thật, input có validation state, card, alert).
- [x] SVG thật (logo, eye/eye-off) — không PNG, không icon font.
- [x] `App\Core\View` — renderer PHP thuần (không template engine), có `e()` escape bắt buộc, `renderWithLayout()`.
- [x] `AuthPageController` (Web, khác với `AuthController` API/JSON) — render trang login/register; redirect nếu đã đăng nhập.
- [x] Trang Đăng nhập + Đăng ký **kết nối thật** vào API đã có (`/api/v1/auth/login`, `/register`) qua `fetch()` thuần (không jQuery/axios) — có xử lý lỗi validation theo từng field, hiển thị thông báo tiếng Việt, toggle hiện/ẩn mật khẩu, loading state trên nút submit.
- [x] Đã thêm `CsrfMiddleware` vào route `/api/v1/auth/*` (trước đó thiếu — cần thiết vì giờ có luồng đăng nhập bằng session/cookie) và expose token qua thẻ `<meta name="csrf-token">`.
- [x] Theme engine (`theme.js`) — light/dark/system, lưu `localStorage`, không reload trang.

**Đã kiểm tra cú pháp JS thật bằng `node --check`** (Node có sẵn trong sandbox) — không lỗi.

### Phase 4 (mở rộng) — đóng toàn bộ liên kết cụt, thêm 5 trang thật
Sau khi tạo dashboard, các link tự tham chiếu tới route chưa tồn tại. Thay vì để nợ kỹ thuật tích lũy, đã đóng toàn bộ ngay trong cùng lượt:
- [x] `/courses` — danh sách khóa học công khai, lọc theo category/grade (`CourseRepository::findPublished()` mới thêm).
- [x] `/courses/{slug}` — trang chi tiết khóa học, hiển thị chương/bài học, nút Đăng ký/Mua nối thật vào `/api/v1/orders/checkout` qua `checkout.js`.
- [x] `/chemistry-tools` — Cân bằng phương trình + Tính khối lượng mol, gọi **thật** vào API Chemistry Engine đã xây ở Phase 6 (`chemistry-tools.js`).
- [x] `/dashboard/course/{uuid}` — trình xem bài học (sidebar chương/bài + nội dung), kiểm tra quyền enrollment/ownership trước khi hiển thị (redirect nếu chưa mua).
- [x] `/orders/{uuid}/payment` — trang hiển thị thông tin chuyển khoản Bank QR (ngân hàng/số tài khoản/số tiền/nội dung — tra đúng theo `payment.bank_account_id` đã lưu, không lấy "tài khoản active hiện tại" có thể đã đổi).
- [x] Đã kiểm chứng bằng script tự động: mọi Controller/View template/CSS/JS/SVG được route hoặc view tham chiếu đều tồn tại trên đĩa — không còn 404 ẩn.

**Lỗi phát hiện và sửa ngay trong lúc viết `CourseDetailPageController`**: gọi nhầm `$this->courses->findActiveEnrollmentOrNull()` (method không tồn tại, sai cả tên lẫn repository — method thật là `OrderRepository::findActiveEnrollment()`). Phát hiện được nhờ chủ động grep xác minh trước khi coi module xong, không phải nhờ chạy thử.

**Giới hạn phạm vi đã biết**: trang thanh toán hiển thị thông tin chuyển khoản dạng bảng text, **không render ảnh QR** (cần thư viện JS tạo QR phía client — chưa thêm để giữ đúng nguyên tắc phụ thuộc tối thiểu). Đây là lựa chọn phạm vi có chủ đích, không phải thiếu sót — `qr_payload` (chuẩn EMVCo) đã có sẵn ở backend, sẵn sàng cho việc thêm renderer QR ở lượt sau nếu cần.

### Phase 4 (tiếp) — Teacher Dashboard + đóng thêm 2 lỗ hổng
- [x] `/dashboard/courses` — phát hiện sidebar Student trỏ tới route này nhưng chưa từng tồn tại (khác với `/dashboard/course/{uuid}` số ít đã có) — đã tạo `MyCoursesPageController` + view.
- [x] `App\Core\NavigationMenus` — tách menu sidebar thành data-driven (`studentMenu()`, `teacherMenu()`) thay vì hardcode trong layout, để dashboard layout dùng chung được cho cả hai vai trò mà không trùng lặp HTML.
- [x] `/teacher/dashboard`, `/teacher/courses`, `/teacher/courses/create`, `/teacher/courses/{uuid}/edit` — toàn bộ đọc/ghi qua API `Course/Chapter/Lesson` đã có từ Phase 5 (không viết lại business logic, chỉ UI + fetch).
- [x] Trang chỉnh sửa khóa học: thêm chương/bài học, xuất bản — tất cả qua fetch thật vào API đã kiểm chứng.

**LỖ HỔNG BẢO MẬT PHÁT HIỆN VÀ SỬA**: route `/api/v1/courses/*` và `/api/v1/orders/checkout` **thiếu `CsrfMiddleware`** dù giờ được gọi từ form/JS chạy trong trình duyệt có session cookie — đây là lỗ hổng CSRF thật (kẻ tấn công có thể dựng trang ngoài submit request tới các endpoint này thay mặt nạn nhân đang đăng nhập). Phát hiện khi nối UI Teacher vào các API này và nhận ra chúng dùng session, không chỉ JWT. Đã thêm `CsrfMiddleware` vào cả hai route group.

**Điều hướng sau đăng nhập đã sửa**: trước đó mọi user đăng nhập xong đều vào `/dashboard` (Student), kể cả giáo viên. Đã thêm `roles` vào response của `/api/v1/auth/login` và điều hướng theo vai trò (`auth.js` + `AuthPageController::postLoginDestination()` dùng chung logic qua `PermissionService::hasRole()`).

### Phase 4 (vá lỗ hổng) — `/dashboard` route
- Phát hiện: trang đăng nhập redirect tới `/dashboard` nhưng route này chưa tồn tại (lỗ hổng trải nghiệm để lại từ lượt trước). Đã vá ngay trong cùng lượt làm việc, không để sang lượt sau:
- [x] `resources/views/layouts/dashboard.php` — layout sidebar + topbar dùng chung cho Student/Teacher (khác nhau ở menu item), CSS riêng `dashboard.css`.
- [x] `DashboardController` (Web) — đọc dữ liệu thật từ `CourseRepository::findEnrolledCoursesForStudent()` (method mới thêm) + `NotificationRepository::getUnreadForUser()` đã có, tự động redirect về `/login` nếu session tham chiếu tới user đã bị xóa (không hiển thị dashboard rỗng/lỗi).
- [x] Route `/dashboard` với `AuthMiddleware`, SVG sidebar (course/flask/logout), `dashboard.js` (nút đăng xuất gọi API thật).

## RỦI RO / NỢ KỸ THUẬT ĐANG BIẾT

1. ~~Trang dashboard có liên kết tới route chưa tồn tại~~ — **ĐÃ SỬA**: đã tạo đầy đủ `/courses`, `/courses/{slug}`, `/dashboard/course/{uuid}`, `/chemistry-tools`, `/orders/{uuid}/payment`. Đã kiểm chứng bằng script: toàn bộ Controller, View template, và CSS/JS/SVG asset được tham chiếu đều tồn tại trên đĩa (không còn liên kết cụt).
2. ~~`BankQrDriver` chưa tồn tại~~ — **ĐÃ SỬA**: `BankQrDriver` + `VietQrPayloadBuilder` đã hoạt động thật (chuẩn VietQR/EMVCo, CRC16-CCITT đúng thuật toán).
3. ~~Session-based login chưa có~~ — **ĐÃ SỬA**: `AuthService::login($identifier, $password, $ip, $useSession)` hỗ trợ cả hai; `AuthController` nhận field `via=session` từ form web.
4. ~~Chưa test được bằng PHP CLI thật~~ — **ĐÃ SỬA**: sandbox hóa ra có sẵn `php-cli`/`mysql-server` cài được qua apt. Đã chạy `php -l` thật trên toàn bộ 126 file (0 lỗi), đã chạy migration+seeder thật trên MySQL 8, đã test toàn bộ luồng nghiệp vụ chính qua HTTP thật bằng `curl` (xem phần "PHIÊN KIỂM THỬ TÍCH HỢP THẬT" ở trên). Môi trường test **không được ship kèm ZIP** (đã dọn `.env`/`vendor`/cache/log trước khi đóng gói) — người dùng cuối vẫn cần tự `composer install` + tạo `.env` + chạy migration theo README.
5. `payment_bank_accounts` đã có seeder (1 tài khoản DEMO) — **bắt buộc thay bằng thông tin ngân hàng thật trước khi vận hành production**, seeder đã ghi cảnh báo rõ ràng.
