-- drop unused stuff in anidb.* tables to speed stuff up

ALTER TABLE `_anime_http` ENGINE=BLACKHOLE;
ALTER TABLE `_lastcheck` ENGINE=BLACKHOLE;
ALTER TABLE `mylist` ENGINE=BLACKHOLE;
ALTER TABLE `review` ENGINE=BLACKHOLE;
ALTER TABLE `groupvote` ENGINE=BLACKHOLE;

ALTER TABLE `file`
DROP INDEX `aid`,
DROP INDEX `gid`,
DROP INDEX `eid`,
DROP INDEX `crc`,
DROP INDEX `ed2k`;
