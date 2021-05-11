SELECT SQL_CALC_FOUND_ROWS  wp_posts.ID FROM wp_posts
INNER JOIN wp_postmeta ON ( wp_posts.ID = wp_postmeta.post_id )
INNER JOIN wp_postmeta AS mt1 ON ( wp_posts.ID = mt1.post_id )
INNER JOIN wp_postmeta AS mt2 ON ( wp_posts.ID = mt2.post_id )
INNER JOIN wp_postmeta AS mt3 ON ( wp_posts.ID = mt3.post_id )
INNER JOIN wp_postmeta AS mt4 ON ( wp_posts.ID = mt4.post_id )
INNER JOIN wp_postmeta AS mt5 ON ( wp_posts.ID = mt5.post_id )
INNER JOIN wp_postmeta AS mt6 ON ( wp_posts.ID = mt6.post_id )
WHERE 1=1
AND
( 
  ( 
    ( wp_postmeta.meta_key = 'surname' AND wp_postmeta.meta_value LIKE '%catherine o\'bannon%' ) 
    OR 
    ( wp_postmeta.meta_key = 'givenname' AND wp_postmeta.meta_value LIKE '%catherine o\'bannon%' ) 
    OR 
    ( wp_postmeta.meta_key = 'surname' AND wp_postmeta.meta_value LIKE '%catherine%o%bannon%' ) 
    OR 
    ( wp_postmeta.meta_key = 'givenname' AND wp_postmeta.meta_value LIKE '%catherine%o%bannon%' ) 
    OR 
    ( 
      ( mt1.meta_key = 'givenname' AND mt1.meta_value LIKE '%catherine%' ) 
      AND 
      ( mt2.meta_key = 'surname' AND mt2.meta_value LIKE '%o%bannon%' )
    ) 
    OR 
    ( 
      ( mt3.meta_key = 'givenname' AND mt3.meta_value LIKE '%catherine%o%' ) 
      AND 
      ( mt4.meta_key = 'surname' AND mt4.meta_value LIKE '%bannon%' )
    )
  ) 
  AND 
  mt5.meta_key = 'surname' 
  AND 
  mt6.meta_key = 'givenname'
)
AND wp_posts.post_type = 'name_entry'
AND ((wp_posts.post_status = 'publish'))
GROUP BY wp_posts.ID
ORDER BY CAST(mt5.meta_value AS CHAR) ASC, CAST(mt6.meta_value AS CHAR) ASC
LIMIT 0, 50



SELECT SQL_CALC_FOUND_ROWS wp_posts.ID FROM `wp_posts`
INNER JOIN wp_postmeta ON (wp_posts.ID = wp_postmeta.post_id)
INNER JOIN wp_postmeta AS mt1 ON (wp_posts.ID = mt1.post_id)
INNER JOIN wp_postmeta AS mt2 ON (wp_posts.ID = mt2.post_id)
INNER JOIN wp_postmeta AS mt3 ON (wp_posts.ID = mt3.post_id)
INNER JOIN wp_postmeta AS mt4 ON (wp_posts.ID = mt4.post_id)
INNER JOIN wp_postmeta AS mt5 ON (wp_posts.ID = mt5.post_id)
INNER JOIN wp_postmeta AS mt6 ON (wp_posts.ID = mt6.post_id)
WHERE 1=1
AND
(
  (
    (wp_postmeta.meta_key = 'surname' AND wp_postmeta.meta_value LIKE '%catherine o\'bannon%')
    OR
    (wp_postmeta.meta_key = 'givenname' AND wp_postmeta.meta_value LIKE '%catherine o\'bannon%')
    OR
    (wp_postmeta.meta_key = 'surname' AND wp_postmeta.meta_value LIKE '%catherine%o%bannon%')
    OR
    (wp_postmeta.meta_key = 'givenname' AND wp_postmeta.meta_value LIKE '%catherine%o%bannon%')
    OR
    (
      (mt1.meta_key = 'givenname' AND mt1.meta_value LIKE '%catherine%')
      AND
      (mt2.meta_key = 'surname' AND mt2.meta_value LIKE '%o%bannon%')
    )
    OR
    (
      (mt3.meta_key = 'givenname' AND mt3.meta_value LIKE '%catherine%o%')
      AND
      (mt4.meta_key = 'surname' AND mt4.meta_value LIKE '%bannon%')
    )
  )
  AND mt5.meta_key = 'surname'
  AND mt6.meta_key = 'givenname'
)
AND wp_posts.post_type = 'name_entry'
AND ((wp_posts.post_status = 'publish'))
GROUP BY wp_posts.ID
ORDER BY CAST(mt1.meta_value AS CHAR) ASC, CAST(mt2.meta_value AS CHAR) ASC LIMIT 0, 50