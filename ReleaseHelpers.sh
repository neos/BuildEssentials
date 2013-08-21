#!/bin/bash

# arguments: VERSION, BRANCH, BUILD_URL
function tag_version {
	local $*

	git tag -f -a -m "[TASK] Tag as version ${VERSION}" -m "See ${BUILD_URL}" -m "Releases: ${BRANCH}" ${VERSION}
}

# arguments: TAG
function push_tag {
	local $*

	git push -f origin ${TAG}:refs/tags/${TAG}
}

# arguments: BRANCH, BUILD_URL
function commit_manifest_update {
	local $*

	if [ "`git status --porcelain composer.json`" ] ; then
		git add composer.json
		git commit -m "[TASK] Update composer manifest" -m "See $BUILD_URL" -m "Releases: ${BRANCH}"
	fi

}

# arguments: BRANCH
function push_branch {
	local $*

	git push origin ${BRANCH}
}