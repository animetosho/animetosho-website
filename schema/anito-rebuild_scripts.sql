-- rebuild comment counts; NOTE: will remove `comments_locked` markers!
TRUNCATE toto_toto_meta;
INSERT INTO toto_toto_meta(id, comments, comments_top)
  SELECT c.mid AS id, c.comments, t.comments AS comments_top
  FROM (
    SELECT mid, COUNT(*) AS comments FROM toto_comments GROUP BY mid
  ) c
  LEFT JOIN (
    SELECT mid, COUNT(*) AS comments FROM toto_comments WHERE replyto=0 GROUP BY mid
  ) t ON t.mid=c.mid;








-- rebuild the comments_all index
TRUNCATE toto_comments_all;
INSERT INTO toto_comments_all
  SELECT "" AS section, cid, message_type, dateline, IF(replyto=0, dateline, (SELECT c.dateline FROM toto_comments c WHERE toto_comments.replytotop=c.cid)) AS top_dateline FROM toto_comments;
INSERT INTO toto_comments_all
  SELECT "feedback" AS section, cid, -1 AS message_type, dateline, IF(replyto=0, dateline, (SELECT c.dateline FROM toto_feedback_comments c WHERE toto_feedback_comments.replytotop=c.cid)) AS top_dateline FROM toto_feedback_comments;
INSERT INTO toto_comments_all
  SELECT "modtalk" AS section, cid, -2 AS message_type, dateline, IF(replyto=0, dateline, (SELECT c.dateline FROM toto_modtalk_comments c WHERE toto_modtalk_comments.replytotop=c.cid)) AS top_dateline FROM toto_modtalk_comments;








-- rebuild latest episode/anime index
TRUNCATE toto_ep_latest;
INSERT INTO toto_ep_latest
  SELECT eid, -MAX(idx_dateline) AS dateline FROM toto_repl.toto_toto WHERE idx_dateline IS NOT NULL AND eid!=0 GROUP BY eid;

TRUNCATE toto_anime_latest;
INSERT INTO toto_anime_latest
  SELECT aid, -MIN(idx_dateline) AS dateline FROM toto_repl.toto_toto WHERE idx_dateline IS NOT NULL GROUP BY aid;

TRUNCATE toto_aniep_latest;
INSERT INTO toto_aniep_latest
  SELECT aeid, aid, eid, MAX(dateline)
  FROM (
    SELECT IF(t.eid=0, -t.aid, t.eid) AS aeid, IF(t.eid=0, t.aid, e.aid) AS aid, t.eid, -MIN(t.idx_dateline) AS dateline
    FROM toto_repl.toto_toto t
    LEFT JOIN anidb.ep e ON t.eid=e.id
    WHERE t.idx_dateline IS NOT NULL
    GROUP BY t.aid, t.eid
  ) aesrc
  GROUP BY aeid;
