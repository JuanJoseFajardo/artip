

select *
from wp_posts
where  post_parent>'0' and post_type='attachment';

UPDATE wp_posts SET post_type = 'product' WHERE ID=2303;

