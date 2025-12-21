@servers(['psilocin' => ['psilocin.empinet.com']])

@task('deploy')
    setfacl -R -m u:33:rwx /srv/stacks/project-echo
    cd /srv/stacks/project-echo
    git stash
    git pull origin master
    docker exec echo-api composer install
    docker exec echo-api php artisan migrate --force
    docker exec echo-api php artisan optimize
@endtask
