# Canonical Project Knowledge Base (`docs/kb/canon/`)  
*(Kho Tri Thức Chuẩn Đã Kiểm Chứng Của Dự Án)*

Two-tray memory isolation (`staging/` -> `canon/`):  
*(Cơ chế cô lập bộ nhớ hai khay (`staging/` -> `canon/`):)*

1. **`docs/kb/staging/` (Unverified Draft Tray)**: Agents drop raw meeting notes, incident observations, and session learnings here (or via `mykit memory stage "<note>"`). Agents MUST NOT treat `staging/` files as binding business rules.  
   *(**`docs/kb/staging/` (Khay Nháp Chưa Kiểm Chứng)**: Agent lưu ghi chú thô, quan sát sự cố và bài học trong phiên tại đây (hoặc qua `mykit memory stage "<note>"`). Agent TUYỆT ĐỐI KHÔNG coi file trong `staging/` là luật nghiệp vụ bắt buộc.)*

2. **`docs/kb/canon/` (Human-Approved Truth)**: Only human-reviewed pricing, product invariants, and confirmed domain rules belong here (or promoted via `mykit memory promote <stg-id>`).  
   *(**`docs/kb/canon/` (Chuẩn Đã Được Con Người Duyệt)**: Chỉ các quy tắc giá, bất biến sản phẩm và luật miền đã được con người duyệt mới nằm tại đây (hoặc được thăng cấp qua `mykit memory promote <stg-id>`).)*

3. **Personal Vault Isolation**: Project agents and external bots are mechanically blocked (`permissions.deny` + `privacy-guard.cjs`) from reading `~/.mykit/personal/**`.  
   *(**Cô Lập Kho Cá Nhân**: Các agent dự án và bot bên ngoài bị chặn cơ học (`permissions.deny` + `privacy-guard.cjs`) không được đọc `~/.mykit/personal/**`.)*
