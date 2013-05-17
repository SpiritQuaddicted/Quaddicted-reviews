This is the source code of the map review system at Quaddicted. It does not include the admin interface.

I am releasing this in the hope that it will invite active contribution of improvements, new features, bug fixes and maybe even some cleaning up.

License
-------
http://www.gnu.org/licenses/agpl-3.0.html so you are free to use this for your own projects as long as you release the source yourself.

Notes
-----
- Uses SQLite 3.
- User authentication is handled through a FluxBB (forum) installation.
- Markdown was added to the comment system in 2013, htmlpurifier is used to validate the content. If you want to use it extract php-markdown-1.0.1p.zip so that markdown.php is in the root and extract htmlpurifier-4.5.0.zip so that the "htmlpurifier-4.5.0" directory is "htmlpurifier" in the root.

Responsible Disclosure
----------------------
If you find a security bug such as SQL injection, arbitrary code injection or path traversal, please do not exploit it but tell me in private. You will receive an awesome rusty metal Quake rune as a reward. I know about the database, there is nothing sensitive inside (everything could be crawled from the website).

Wishlist
--------
- Templates, so code and layout are a bit less messy and developing is more fun.
- Letting people change their ratings (with some rate limiting of course).
- Think of something smart to support more than one screenshot (think of speedmapping packs where all maps should have one screenshot).
- Make index_bayes.php work even if one is logged in (I forgot what the issue is).
- A comprehensive search function

Spirit ( spirit ät quaddicted döt com )