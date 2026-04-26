-- drop unused indexes
DROP INDEX IF EXISTS `tracker_queried` ON `toto_tracker_stats`;
DROP INDEX IF EXISTS `size_crc` ON `toto_files`;
DROP INDEX IF EXISTS `hash` ON `toto_attachment_files`;

