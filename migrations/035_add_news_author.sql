-- Ghi lai tai khoan admin da TAO MOI moi bai viet/chinh sach, de hien ten
-- tac gia that thay vi chu cung "Phuc Thinh Solar" giong nhau cho tat ca.
-- Bai cu (id_user=0, chua co du lieu nay) van hien ten mac dinh nhu truoc.
ALTER TABLE `table_news`
    ADD COLUMN IF NOT EXISTS `id_user` INT(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `id`;

-- Rollback: ALTER TABLE `table_news` DROP COLUMN `id_user`;
