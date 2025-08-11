<?php
class SitemapController {
    public function robots(): void {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\nAllow: /\nSitemap: " . base_url('/sitemap.xml');
    }

    public function sitemap(): void {
        header('Content-Type: application/xml; charset=utf-8');
        $urls = [];
        $urls[] = base_url('/');
        $urls[] = base_url('/numeriai');
        $urls[] = base_url('/paieska');
        $urls[] = base_url('/pasalinimas');
        // Known numbers with activity
        $stmt = db()->query("SELECT e164, updated_at FROM numbers WHERE views > 0 OR id IN (SELECT DISTINCT number_id FROM comments) ORDER BY updated_at DESC LIMIT 50000");
        $nums = $stmt->fetchAll();
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($urls as $u) {
            echo "  <url><loc>" . esc($u) . "</loc></url>\n";
        }
        foreach ($nums as $n) {
            $loc = base_url('/' . $n['e164']);
            $lastmod = date('c', strtotime($n['updated_at']));
            echo "  <url><loc>" . esc($loc) . "</loc><lastmod>" . esc($lastmod) . "</lastmod></url>\n";
        }
        echo "</urlset>";
    }
}