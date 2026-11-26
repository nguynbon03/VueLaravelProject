# Engineering Constitution & Immutable Laws (`docs/LAW.md`)  
*(Hiến Pháp Kỹ Thuật & Các Luật Bất Biến Cấp Hệ Thống)*

## 1. Architectural & Module Boundaries  
*(Ranh Giới Kiến Trúc & Phân Hệ)*

- Keep domain and business logic free of HTTP, UI, and third-party SDK coupling (`backend/app/Modules/*/Domain/**` is 100% Pure PHP).  
  *(Giữ logic miền và nghiệp vụ tách biệt hoàn toàn khỏi HTTP, giao diện và SDK bên thứ 3 (`backend/app/Modules/*/Domain/**` thuần 100% PHP).)*

- Cross-module communication goes through explicit `Domain\Contracts\*Port` interfaces rather than reaching directly into foreign database tables or adapters.  
  *(Giao tiếp liên module bắt buộc đi qua các interface `Domain\Contracts\*Port` tường minh thay vì truy cập trực tiếp bảng DB hay Adapter của module khác.)*

---

## 2. Security, Financial & Concurrency Invariants  
*(Các Bất Biến Bảo Mật, Tài Chính & Đồng Thời)*

- Never hardcode secrets or tokens; load configuration from environment variables only.  
  *(Tuyệt đối không gắn cứng bí mật hay token trong mã nguồn; chỉ nạp cấu hình từ biến môi trường.)*

- Store all monetary amounts as integer minor units (`BIGINT` + `Money` Value Object), sort variant IDs (`ksort`) before `SELECT ... FOR UPDATE`, and enforce `Idempotency-Key` + `HMAC-SHA256` webhook inbox verification.  
  *(Lưu mọi số tiền dưới dạng số nguyên đơn vị nhỏ nhất (`BIGINT` + `Money` Value Object), sắp xếp ID biến thể (`ksort`) trước khi khóa dòng `SELECT ... FOR UPDATE`, và bắt buộc kiểm tra `Idempotency-Key` cùng hộp thư đến webhook `HMAC-SHA256`.)*

---

## 3. Repository Hygiene Law  
*(Luật Vệ Sinh Kho Mã Nguồn)*

- Root keeps only `README.md`, `AGENTS.md`, `CLAUDE.md` (symlink), and top-level orchestration (`compose.yaml`).  
  *(Thư mục gốc chỉ giữ `README.md`, `AGENTS.md`, `CLAUDE.md` (symlink) và file điều phối cấp cao (`compose.yaml`).)*

- Co-locate Dockerfiles, `.env.example`, and UI design tokens (`DESIGN.md`) inside their respective service folders (`backend/`, `frontend/`).  
  *(Đặt `Dockerfile`, `.env.example` và bộ quy chuẩn giao diện (`DESIGN.md`) ngay bên trong thư mục dịch vụ tương ứng (`backend/`, `frontend/`).)*
