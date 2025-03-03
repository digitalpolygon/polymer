RUN composer global config --no-plugins allow-plugins.ion-bazan/composer-diff true; \
      composer global require ion-bazan/composer-diff --no-interaction;
#RUN python -m pip install --upgrade pip
RUN pip install --break-system-packages \
        mkdocs==1.5.3 \
        mkdocs-material \
        mike \
        mkdocs-git-authors-plugin \
        mkdocs-redirects \
        mkdocs-git-revision-date-localized-plugin \
        pymdown-extensions \
        git+https://gitlab.com/blacs30/mkdocs-edit-url.git;
