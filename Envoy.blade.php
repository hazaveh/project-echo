@servers(['psilocin' => ['psilocin.empinet.com']])

@task('deploy')
    cd /srv/stacks/project-echo
    git stash
    git pull origin master
    setfacl -R -m u:33:rwx /srv/stacks/project-echo
    docker exec echo-api composer install
    docker exec echo-api php artisan migrate --force
    docker exec echo-api php artisan optimize
    docker run --rm -v /srv/stacks/project-echo:/app -w /app node:20 bash -c "npm install && npm run build"
@endtask
