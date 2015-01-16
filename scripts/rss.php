<? # Generates RSS feed for BBOX. Written by Ilya Zverev, licensed WTFPL.
require("db.inc.php");
$filter = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : 0;
$bbox = parse_bbox(isset($_REQUEST['bbox']) ? $_REQUEST['bbox'] : '');
if( !$bbox ) {
    print 'error: BBox required.';
    exit;
}
header('Content-type: application/rss+xml; charset=utf-8');
$db = connect();
$sql = "select c.* from wdi_tiles t, wdi_changesets c where t.changeset_id = c.changeset_id and t.lon >= $bbox[0] and t.lon <= $bbox[2] and t.lat >= $bbox[1] and t.lat <= $bbox[3] group by c.changeset_id order by c.change_time desc limit ".($filter ? '150' : '20');
$res = $db->query($sql);
$bbox_str = $bbox[0]*$tile_size.','.$bbox[1]*$tile_size.','.($bbox[2]+1)*$tile_size.','.($bbox[3]+1)*$tile_size;
//\t<link>http://openstreetmap.org/?box=yes&amp;bbox=$bbox_str</link>
$latlon = 'lat='.(($bbox[3]+$bbox[1])*$tile_size/2).'&amp;lon='.(($bbox[2]+$bbox[0])*$tile_size/2);
print <<<"EOT"
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
<channel>
\t<title>WhoDidIt Feed for BBOX [$bbox_str]</title>
\t<description>WhoDidIt feed for BBOX [$bbox_str]</description>
\t<link>$frontend_url?$latlon&amp;zoom=12</link>
\t<generator>WhoDidIt</generator>
\t<ttl>60</ttl>

EOT;
date_default_timezone_set('UTC');
$count = 20;
while( $row = $res->fetch_assoc() ) {
    $susp = is_changeset_suspicious($row) ? '[!] ' : '';
    if( $filter && !$susp ) continue;
    $untitled = !$row['comment'] || strlen($row['comment']) <= 2 || substr($row['comment'], 0, 5) == 'BBOX:';
    print "\t<item>\n";
    print "\t\t<title>${susp}User ".htmlspecialchars($row['user_name'])." has uploaded ".($untitled?'an untitled ':'a ')."changeset".($untitled?'':': &quot;'.htmlspecialchars($row['comment']).'&quot;')."</title>\n";
    print "\t\t<link>http://openstreetmap.org/browse/changeset/${row['changeset_id']}</link>\n";
    $date = strtotime($row['change_time']);
    $date_str = date(DATE_RSS, $date);
    print "\t\t<pubDate>$date_str</pubDate>\n";
    $desc = "User <a href=\"http://openstreetmap.org/user/".rawurlencode($row['user_name'])."\">".htmlspecialchars($row['user_name'])."</a> has uploaded <a href=\"http://openstreetmap.org/browse/changeset/${row['changeset_id']}\">a changeset</a> in your watched area using ".htmlspecialchars($row['created_by']).", titled \"".htmlspecialchars($row['comment'])."\". <a href=\"$frontend_url?changeset=${row['changeset_id']}&show=1\">Show it on WhoDidIt</a> or <a href=\"https://overpass-api.de/achavi/?changeset=${row['changeset_id']}\">in Achavi</a>.";
    $desc .= '<br><br>Statistics:<ul>';
    $desc .= '<li>Nodes: '.$row['nodes_created'].' created, '.$row['nodes_modified'].' modified, '.$row['nodes_deleted'].' deleted</li>';
    $desc .= '<li>Ways: '.$row['ways_created'].' created, '.$row['ways_modified'].' modified, '.$row['ways_deleted'].' deleted</li>';
    $desc .= '<li>Relations: '.$row['relations_created'].' created, '.$row['relations_modified'].' modified, '.$row['relations_deleted'].' deleted</li></ul>';
    print "\t\t<description>".htmlspecialchars($desc)."</description>\n";
    print "\t</item>\n";
    if( --$count <= 0 ) break;
}
print "</channel>\n</rss>";
?>
