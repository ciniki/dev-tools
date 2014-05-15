#!/bin/bash

cd site/ciniki-mods/artcatalog; git checkout master; git remote add push git@github.com:ciniki/artcatalog.git; cd ../../..
cd site/ciniki-mods/artgallery; git checkout master; git remote add push git@github.com:ciniki/artgallery.git; cd ../../..
cd site/ciniki-mods/atdo; git checkout master; git remote add push git@github.com:ciniki/atdo.git; cd ../../..
cd site/ciniki-mods/blog; git checkout master; git remote add push git@github.com:ciniki/blog.git; cd ../../..
cd site/ciniki-mods/bugs; git checkout master; git remote add push git@github.com:ciniki/bugs.git; cd ../../..
cd site/ciniki-mods/businesses; git checkout master; git remote add push git@github.com:ciniki/businesses.git; cd ../../..
cd site/ciniki-mods/calendars; git checkout master; git remote add push git@github.com:ciniki/calendars.git; cd ../../..
cd site/ciniki-mods/clicktracker; git checkout master; git remote add push git@github.com:ciniki/clicktracker.git; cd ../../..
cd site/ciniki-mods/core; git checkout master; git remote add push git@github.com:ciniki/core.git; cd ../../..
cd site/ciniki-mods/courses; git checkout master; git remote add push git@github.com:ciniki/courses.git; cd ../../..
cd site/ciniki-mods/cron; git checkout master; git remote add push git@github.com:ciniki/cron.git; cd ../../..
cd site/ciniki-mods/customers; git checkout master; git remote add push git@github.com:ciniki/customers.git; cd ../../..
cd site/ciniki-mods/directory; git checkout master; git remote add push git@github.com:ciniki/directory.git; cd ../../..
cd site/ciniki-mods/events; git checkout master; git remote add push git@github.com:ciniki/events.git; cd ../../..
cd site/ciniki-mods/exhibitions; git checkout master; git remote add push git@github.com:ciniki/exhibitions.git; cd ../../..
cd site/ciniki-mods/filedepot; git checkout master; git remote add push git@github.com:ciniki/filedepot.git; cd ../../..
cd site/ciniki-mods/gallery; git checkout master; git remote add push git@github.com:ciniki/gallery.git; cd ../../..
cd site/ciniki-mods/images; git checkout master; git remote add push git@github.com:ciniki/images.git; cd ../../..
cd site/ciniki-mods/info; git checkout master; git remote add push git@github.com:ciniki/info.git; cd ../../..
cd site/ciniki-mods/links; git checkout master; git remote add push git@github.com:ciniki/links.git; cd ../../..
cd site/ciniki-mods/mail; git checkout master; git remote add push git@github.com:ciniki/mail.git; cd ../../..
cd site/ciniki-mods/marketing; git checkout master; git remote add push git@github.com:ciniki/marketing.git; cd ../../..
cd site/ciniki-mods/newsaggregator; git checkout master; git remote add push git@github.com:ciniki/newsaggregator.git; cd ../../..
cd site/ciniki-mods/newsletters; git checkout master; git remote add push git@github.com:ciniki/newsletters.git; cd ../../..
cd site/ciniki-mods/products; git checkout master; git remote add push git@github.com:ciniki/products.git; cd ../../..
cd site/ciniki-mods/projects; git checkout master; git remote add push git@github.com:ciniki/projects.git; cd ../../..
cd site/ciniki-mods/recipes; git checkout master; git remote add push git@github.com:ciniki/recipes.git; cd ../../..
cd site/ciniki-mods/sapos; git checkout master; git remote add push git@github.com:ciniki/sapos.git; cd ../../..
cd site/ciniki-mods/services; git checkout master; git remote add push git@github.com:ciniki/services.git; cd ../../..
cd site/ciniki-mods/sponsors; git checkout master; git remote add push git@github.com:ciniki/sponsors.git; cd ../../..
cd site/ciniki-mods/subscriptions; git checkout master; git remote add push git@github.com:ciniki/subscriptions.git; cd ../../..
cd site/ciniki-mods/surveys; git checkout master; git remote add push git@github.com:ciniki/surveys.git; cd ../../..
cd site/ciniki-mods/sysadmin; git checkout master; git remote add push git@github.com:ciniki/sysadmin.git; cd ../../..
cd site/ciniki-mods/systemdocs; git checkout master; git remote add push git@github.com:ciniki/systemdocs.git; cd ../../..
cd site/ciniki-mods/taxes; git checkout master; git remote add push git@github.com:ciniki/taxes.git; cd ../../..
cd site/ciniki-mods/toolbox; git checkout master; git remote add push git@github.com:ciniki/toolbox.git; cd ../../..
cd site/ciniki-mods/users; git checkout master; git remote add push git@github.com:ciniki/users.git; cd ../../..
cd site/ciniki-mods/web; git checkout master; git remote add push git@github.com:ciniki/web.git; cd ../../..
cd site/ciniki-mods/wineproduction; git checkout master; git remote add push git@github.com:ciniki/wineproduction.git; cd ../../..
cd site/ciniki-mods/workshops; git checkout master; git remote add push git@github.com:ciniki/workshops.git; cd ../../..

cd site/ciniki-manage-themes/default; git checkout master; git remote add push git@github.com:ciniki/manage-theme-default.git; cd ../../..

# 
# Set the directory permissions
#
chmod a+w site/ciniki-cache
chmod a+w site/ciniki-mods/images/cache
chmod a+w site/ciniki-mods/web/cache
