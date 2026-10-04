import json, os, zipfile, sys
sys.path.insert(0,'.')
from pages import PAGES
from xml.sax.saxutils import escape
out="./site/out"; os.makedirs(out+"/vorlagen",exist_ok=True)
def cdata(s): return "<![CDATA["+s.replace("]]>","]]]]><![CDATA[>")+"]]>"
items=[]; zf=zipfile.ZipFile(out+"/dj-kolja-one-vorlagen.zip","w",zipfile.ZIP_DEFLATED)
for i,(title,slug,fn) in enumerate(PAGES):
    content=fn(); data=json.dumps(content,ensure_ascii=False)
    tpl={"version":"0.4","title":f"DJ KOLJA ONE – {title}","type":"page","content":content,"page_settings":{"template":"elementor_canvas"}}
    fname=f"{i+1:02d}-{slug}.json"
    s=json.dumps(tpl,ensure_ascii=False); open(f"{out}/vorlagen/{fname}","w").write(s); zf.writestr(fname,s)
    pid=5000+i
    meta={"_elementor_data":data,"_elementor_edit_mode":"builder","_elementor_template_type":"wp-page","_elementor_version":"4.3.2","_wp_page_template":"elementor_canvas"}
    metas="".join(f"<wp:postmeta><wp:meta_key>{cdata(k)}</wp:meta_key><wp:meta_value>{cdata(v)}</wp:meta_value></wp:postmeta>" for k,v in meta.items())
    items.append(f"""<item><title>{cdata(title)}</title><link>/{slug}/</link><pubDate>Mon, 28 Sep 2026 20:00:00 +0000</pubDate>
<dc:creator>{cdata("admin")}</dc:creator><guid isPermaLink="false">/?page_id={pid}</guid><description></description>
<content:encoded>{cdata("")}</content:encoded><excerpt:encoded>{cdata("")}</excerpt:encoded>
<wp:post_id>{pid}</wp:post_id><wp:post_date>{cdata("2026-09-28 20:00:00")}</wp:post_date><wp:post_date_gmt>{cdata("2026-09-28 18:00:00")}</wp:post_date_gmt>
<wp:post_modified>{cdata("2026-09-28 20:00:00")}</wp:post_modified><wp:post_modified_gmt>{cdata("2026-09-28 18:00:00")}</wp:post_modified_gmt>
<wp:comment_status>{cdata("closed")}</wp:comment_status><wp:ping_status>{cdata("closed")}</wp:ping_status><wp:post_name>{cdata(slug)}</wp:post_name>
<wp:status>{cdata("publish")}</wp:status><wp:post_parent>0</wp:post_parent><wp:menu_order>{i}</wp:menu_order><wp:post_type>{cdata("page")}</wp:post_type>
<wp:post_password>{cdata("")}</wp:post_password><wp:is_sticky>0</wp:is_sticky>{metas}</item>""")
zf.close()
# Seiten, die das Plugin per Knopf einspielt (kjo_seiten_sync)
AUTO=("start","hochzeits-dj","geburtstags-dj","firmenfeier-dj","event-dj","ueber-mich","einsatzgebiete","dj-memmingen","dj-ulm","dj-biberach","dj-ravensburg","dj-kempten","dj-fuessen","dj-kaufbeuren","dj-landsberg")
sd="./plugin/dj-kolja-one-plattenkiste/seiten"; os.makedirs(sd,exist_ok=True)
for title,slug,fn in PAGES:
    if slug in AUTO: open(f"{sd}/{slug}.json","w",encoding="utf-8").write(json.dumps(fn(),ensure_ascii=False,separators=(",",":")))
xml=f"""<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0" xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.2/">
<channel><title>DJ KOLJA ONE</title><link>http://dj-kolja-one.de</link><description>Jedes Event findet nur einmal statt.</description>
<pubDate>Mon, 28 Sep 2026 20:00:00 +0000</pubDate><language>de-DE</language><wp:wxr_version>1.2</wp:wxr_version>
<wp:base_site_url>http://dj-kolja-one.de</wp:base_site_url><wp:base_blog_url>http://dj-kolja-one.de</wp:base_blog_url>
<wp:author><wp:author_id>1</wp:author_id><wp:author_login>{cdata("admin")}</wp:author_login><wp:author_email>{cdata("")}</wp:author_email><wp:author_display_name>{cdata("admin")}</wp:author_display_name><wp:author_first_name>{cdata("")}</wp:author_first_name><wp:author_last_name>{cdata("")}</wp:author_last_name></wp:author>
{''.join(items)}
</channel></rss>"""
open(out+"/dj-kolja-one-alle-seiten.xml","w").write(xml)
import xml.dom.minidom; xml.dom.minidom.parseString(open(out+"/dj-kolja-one-alle-seiten.xml","rb").read()); print("xml ok", len(PAGES))
