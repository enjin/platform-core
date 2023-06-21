vendor/bin/testbench serve --port 9091 &
sleep 5
graphql-inspector introspect http://localhost:9091/graphql --write schema/schema.gql
graphql-inspector introspect http://localhost:9091/graphql --write schema/schema.gql
fuser -k 9091/tcp
