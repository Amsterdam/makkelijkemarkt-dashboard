.PHONY: manifests deploy

dc = docker-compose

ENVIRONMENT ?= local
HELM_ARGS = manifests/chart \
	-f manifests/values.yaml \
	-f manifests/env/${ENVIRONMENT}.yaml \
	--set image.tag=${VERSION}

REGISTRY ?= 127.0.0.1:5001
REPOSITORY ?= salmagundi/mm-dashboard
VERSION ?= latest
CHART_VERSION?=1.9.1

build:
	$(dc) build

test:
	echo "No tests available"

migrate:
	kubectl exec -it deploy/mm-dashboard-mm-dashboard -- sh -c "php bin/console --no-interaction doctrine:migrations:migrate"
	kubectl exec -it deploy/mm-dashboard-mm-dashboard -- sh -c "php bin/console doc:fix:load  --no-interaction --purge-with-truncate"

push:
	$(dc) push


manifests:
	@helm template mm-dashboard $(HELM_ARGS) $(ARGS)

deploy: manifests
	helm upgrade --install mm-dashboard $(HELM_ARGS) $(ARGS)

update-chart:
	rm -rf manifests/chart
	git clone --branch $(CHART_VERSION) --depth 1 git@github.com:Amsterdam/helm-application.git manifests/chart
	rm -rf manifests/chart/.git

clean:
	$(dc) down -v --remove-orphans

reset:
	kubectl delete deployments --all && helm uninstall mm-dashboard

refresh: reset build push deploy

dev:
	nohup kubycat kubycat-config.yaml > /dev/null 2>&1&
