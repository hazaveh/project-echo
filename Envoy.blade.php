@servers(['acid25' => ['acid25.empinet.com']])

@task('deploy')
    cd /root/apps/project-echo
    git stash
    git pull origin master
    docker exec echo-api composer install
    chown 33:33 * -R
@endtask