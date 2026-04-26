ALTER TABLE nyaasi_torrents
  DROP INDEX IF EXISTS sub_category_index,
  DROP INDEX IF EXISTS listing_check_index,
  DROP INDEX IF EXISTS uploader_check_index,
  DROP COLUMN IF EXISTS idx_class;
ALTER TABLE nyaasis_torrents
  DROP INDEX IF EXISTS sub_category_index,
  DROP INDEX IF EXISTS listing_check_index,
  DROP INDEX IF EXISTS uploader_check_index,
  DROP COLUMN IF EXISTS idx_class;


ALTER TABLE `tosho_torrents` ENGINE = BLACKHOLE;
ALTER TABLE `anidex_torrents` ENGINE = BLACKHOLE;
ALTER TABLE `anidex_users` ENGINE = BLACKHOLE;
ALTER TABLE `anidex_torrent_comments` ENGINE = BLACKHOLE;
ALTER TABLE `anidex_groups` ENGINE = BLACKHOLE;
ALTER TABLE `anidex_group_members` ENGINE = BLACKHOLE;
ALTER TABLE `anidex_group_comments` ENGINE = BLACKHOLE;
